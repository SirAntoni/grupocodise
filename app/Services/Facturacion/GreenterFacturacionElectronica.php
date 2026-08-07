<?php

namespace App\Services\Facturacion;

use App\Models\CreditNote;
use App\Models\DispatchGuide;
use App\Models\Invoice;
use App\Services\FacturacionElectronica;
use App\Support\NumeroALetras;
use Greenter\Api;
use Greenter\Model\Client\Client as GreenterClient;
use Greenter\Model\Company\Address;
use Greenter\Model\Company\Company;
use Greenter\Model\Despatch\Despatch;
use Greenter\Model\Despatch\DespatchDetail;
use Greenter\Model\Despatch\Direction;
use Greenter\Model\Despatch\Driver;
use Greenter\Model\Despatch\Shipment;
use Greenter\Model\Despatch\Transportist;
use Greenter\Model\Despatch\Vehicle;
use Greenter\Model\Sale\Cuota;
use Greenter\Model\Sale\Detraction;
use Greenter\Model\Sale\FormaPagos\FormaPagoContado;
use Greenter\Model\Sale\FormaPagos\FormaPagoCredito;
use Greenter\Model\Sale\Invoice as GreenterInvoice;
use Greenter\Model\Sale\Legend;
use Greenter\Model\Sale\Note;
use Greenter\Model\Sale\SaleDetail;
use Greenter\Report\XmlUtils;
use Greenter\See;

class GreenterFacturacionElectronica implements FacturacionElectronica
{
    public function sendInvoice(Invoice $invoice): SunatResult
    {
        $see = $this->makeSee();
        $document = $this->buildInvoice($invoice);

        $result = $see->send($document);
        $xml = $see->getFactory()->getLastXml();

        return $this->normalizeSeeResult($result, $xml);
    }

    public function sendCreditNote(CreditNote $creditNote): SunatResult
    {
        $see = $this->makeSee();
        $document = $this->buildCreditNote($creditNote);

        $result = $see->send($document);
        $xml = $see->getFactory()->getLastXml();

        return $this->normalizeSeeResult($result, $xml);
    }

    public function sendDispatchGuide(DispatchGuide $guide): SunatResult
    {
        $api = $this->makeApi();
        $document = $this->buildDespatch($guide);

        $result = $api->send($document);
        $xml = $api->getLastXml();
        $hash = $xml ? (new XmlUtils)->getHashSign($xml) : null;

        if (! $result->isSuccess()) {
            $error = $result->getError();

            return $this->isDefinitiveRejection($error?->getCode())
                ? SunatResult::rejected($error?->getCode(), $error?->getMessage(), $xml)
                : SunatResult::error($error?->getCode(), $error?->getMessage(), $xml);
        }

        $ticket = method_exists($result, 'getTicket') ? $result->getTicket() : null;

        if ($ticket) {
            return SunatResult::pending($ticket, $xml, $hash);
        }

        return SunatResult::accepted(null, 'Aceptada', $xml, null, $hash);
    }

    public function checkDispatchGuideStatus(DispatchGuide $guide, string $ticket): SunatResult
    {
        $api = $this->makeApi();
        $result = $api->getStatus($ticket);

        if (! $result->isSuccess()) {
            $error = $result->getError();

            // Un rechazo por contenido no se arregla reintentando: hay que
            // corregir la guía y volver a enviarla desde cero (ticket nuevo).
            return $this->isDefinitiveRejection($error?->getCode())
                ? SunatResult::rejected($error?->getCode(), $error?->getMessage())
                : SunatResult::error($error?->getCode(), $error?->getMessage());
        }

        $cdr = $result->getCdrResponse();
        $cdrZip = method_exists($result, 'getCdrZip') ? $result->getCdrZip() : null;

        if ($cdr === null) {
            // Aún en proceso.
            return SunatResult::pending($ticket, null, null);
        }

        $code = (string) $cdr->getCode();

        if ($code === '0' || (int) $code >= 4000) {
            return SunatResult::accepted($code, $cdr->getDescription(), null, $cdrZip, null, (int) $code >= 4000);
        }

        return SunatResult::rejected($code, $cdr->getDescription(), null, $cdrZip);
    }

    /* ----------------------------------------------------------------- */

    /**
     * SUNAT clasifica sus códigos por rango: del 100 al 999 son fallas del
     * servicio (vale la pena reintentar), del 1000 al 3999 rechazan el
     * comprobante (hay que corregirlo), y de 4000 en adelante son
     * observaciones sobre un documento aceptado.
     */
    protected function isDefinitiveRejection(?string $code): bool
    {
        if ($code === null || ! preg_match('/\d/', $code)) {
            return false;
        }

        $number = (int) preg_replace('/\D/', '', $code);

        return $number >= 1000 && $number < 4000;
    }

    protected function makeSee(): See
    {
        $env = config('facturacion.environment');

        $see = new See;
        $see->setService(config("facturacion.endpoints.{$env}.see"));
        $see->setCertificate(file_get_contents(config('facturacion.certificate.path')));
        $see->setClaveSOL(
            config('facturacion.company.ruc'),
            config('facturacion.sol.user'),
            config('facturacion.sol.password'),
        );
        $see->setBuilderOptions(['cache' => storage_path('framework/cache/greenter'), 'strict_variables' => true]);

        return $see;
    }

    protected function makeApi(): Api
    {
        $env = config('facturacion.environment');

        $api = new Api([
            'auth' => config("facturacion.endpoints.{$env}.gre_auth"),
            'cpe' => config("facturacion.endpoints.{$env}.gre_cpe"),
        ]);
        $api->setApiCredentials(
            config('facturacion.gre_api.client_id'),
            config('facturacion.gre_api.client_secret'),
        );
        $api->setClaveSOL(
            config('facturacion.company.ruc'),
            config('facturacion.sol.user'),
            config('facturacion.sol.password'),
        );
        $api->setCertificate(file_get_contents(config('facturacion.certificate.path')));
        $api->setBuilderOptions(['cache' => storage_path('framework/cache/greenter'), 'strict_variables' => true]);

        return $api;
    }

    protected function normalizeSeeResult(mixed $result, ?string $xml): SunatResult
    {
        $hash = null;
        if ($xml) {
            try {
                $hash = (new XmlUtils)->getHashSign($xml);
            } catch (\Throwable) {
                $hash = null;
            }
        }

        if (! $result->isSuccess()) {
            $error = $result->getError();
            $code = $error?->getCode();

            // Errores 2000-3999 son rechazos del comprobante; el resto son
            // fallas de comunicación/servicio (reintentables).
            $numeric = (int) $code;
            if ($numeric >= 2000 && $numeric < 4000) {
                return SunatResult::rejected($code, $error?->getMessage(), $xml);
            }

            return SunatResult::error($code, $error?->getMessage(), $xml);
        }

        $cdr = $result->getCdrResponse();
        $code = (string) $cdr->getCode();
        $cdrZip = $result->getCdrZip();

        if ($code === '0' || (int) $code >= 4000) {
            $notes = implode('; ', $cdr->getNotes() ?? []);
            $message = trim($cdr->getDescription().($notes !== '' ? " Observaciones: {$notes}" : ''));

            return SunatResult::accepted($code, $message, $xml, $cdrZip, $hash, (int) $code >= 4000);
        }

        return SunatResult::rejected($code, $cdr->getDescription(), $xml, $cdrZip);
    }

    protected function company(): Company
    {
        $config = config('facturacion.company');

        return (new Company)
            ->setRuc($config['ruc'])
            ->setRazonSocial($config['razon_social'])
            ->setNombreComercial($config['nombre_comercial'] ?? $config['razon_social'])
            ->setAddress((new Address)
                ->setUbigueo($config['address']['ubigeo'])
                ->setDepartamento($config['address']['departamento'])
                ->setProvincia($config['address']['provincia'])
                ->setDistrito($config['address']['distrito'])
                ->setDireccion($config['address']['direccion']));
    }

    protected function clientFor(\App\Models\Client $client): GreenterClient
    {
        return (new GreenterClient)
            ->setTipoDoc('6')
            ->setNumDoc($client->ruc)
            ->setRznSocial($client->business_name);
    }

    protected function buildInvoice(Invoice $invoice): GreenterInvoice
    {
        $details = $invoice->items->map(fn ($item) => $this->saleDetail(
            code: $item->product?->code ?? 'SERV-ADIC',
            unit: $item->unit_code,
            description: $item->description,
            quantity: (float) $item->quantity,
            unitValue: (float) $item->unit_value,
            subtotal: (float) $item->subtotal,
            igv: (float) $item->igv,
            igvRate: (float) $item->igv_rate,
            affectation: $item->igv_affectation_code,
        ))->all();

        // Catálogo 51: 1001 identifica la operación sujeta a detracción (SPOT).
        $document = (new GreenterInvoice)
            ->setUblVersion('2.1')
            ->setTipoDoc('01')
            ->setTipoOperacion($invoice->has_detraction ? '1001' : '0101')
            ->setSerie($invoice->series->code)
            ->setCorrelativo((string) $invoice->number)
            ->setFechaEmision($invoice->issue_date->toDateTime())
            ->setFecVencimiento($invoice->due_date?->toDateTime())
            ->setTipoMoneda($invoice->currency)
            ->setCompany($this->company())
            ->setClient($this->clientFor($invoice->client))
            ->setMtoOperGravadas((float) $invoice->taxable_amount)
            ->setMtoIGV((float) $invoice->igv)
            ->setTotalImpuestos((float) $invoice->igv)
            ->setValorVenta((float) $invoice->taxable_amount)
            ->setSubTotal((float) $invoice->total)
            ->setMtoImpVenta((float) $invoice->total)
            ->setDetails($details);

        $legends = [
            (new Legend)->setCode('1000')->setValue(NumeroALetras::legend((float) $invoice->total)),
        ];

        if ($invoice->has_detraction) {
            // Catálogo 52: 2006 es la leyenda obligatoria del SPOT.
            $legends[] = (new Legend)->setCode('2006')->setValue('Operación sujeta a detracción');

            $document->setDetraccion((new Detraction)
                ->setCodBienDetraccion($invoice->detraction_code)
                ->setCodMedioPago(config('facturacion.detraccion.medio_pago'))
                ->setCtaBanco(config('facturacion.detraccion.cuenta_banco_nacion'))
                ->setPercent((float) $invoice->detraction_percent)
                ->setMount((float) $invoice->detraction_amount));
        }

        $document->setLegends($legends);

        // SUNAT exige declarar la forma de pago; al crédito lleva cuota única
        // con el vencimiento pactado en la factura. Cuando hay detracción, el
        // cliente solo transfiere el neto: eso es lo que queda por pagar.
        if ($invoice->payment_type === 'credito') {
            $porPagar = round((float) $invoice->total - (float) ($invoice->detraction_amount ?? 0), 2);

            $document->setFormaPago(new FormaPagoCredito($porPagar, $invoice->currency));
            $document->setCuotas([
                (new Cuota)
                    ->setMoneda($invoice->currency)
                    ->setMonto($porPagar)
                    ->setFechaPago($invoice->due_date->toDateTime()),
            ]);
        } else {
            $document->setFormaPago(new FormaPagoContado);
        }

        return $document;
    }

    protected function buildCreditNote(CreditNote $creditNote): Note
    {
        $invoice = $creditNote->invoice;

        $details = $invoice->items->map(fn ($item) => $this->saleDetail(
            code: $item->product?->code ?? 'SERV-ADIC',
            unit: $item->unit_code,
            description: $item->description,
            quantity: (float) $item->quantity,
            unitValue: (float) $item->unit_value,
            subtotal: (float) $item->subtotal,
            igv: (float) $item->igv,
            igvRate: (float) $item->igv_rate,
            affectation: $item->igv_affectation_code,
        ))->all();

        return (new Note)
            ->setUblVersion('2.1')
            ->setTipoDoc('07')
            ->setSerie($creditNote->series->code)
            ->setCorrelativo((string) $creditNote->number)
            ->setFechaEmision($creditNote->issue_date->toDateTime())
            ->setTipDocAfectado('01')
            ->setNumDocfectado($creditNote->affected_full_number ?? $invoice->full_number)
            ->setCodMotivo($creditNote->motive_code)
            ->setDesMotivo($creditNote->motive_description)
            ->setTipoMoneda($creditNote->currency)
            ->setCompany($this->company())
            ->setClient($this->clientFor($invoice->client))
            ->setMtoOperGravadas((float) $creditNote->taxable_amount)
            ->setMtoIGV((float) $creditNote->igv)
            ->setTotalImpuestos((float) $creditNote->igv)
            ->setValorVenta((float) $creditNote->taxable_amount)
            ->setSubTotal((float) $creditNote->total)
            ->setMtoImpVenta((float) $creditNote->total)
            ->setDetails($details)
            ->setLegends([
                (new Legend)->setCode('1000')->setValue(NumeroALetras::legend((float) $creditNote->total)),
            ]);
    }

    protected function saleDetail(
        string $code,
        string $unit,
        string $description,
        float $quantity,
        float $unitValue,
        float $subtotal,
        float $igv,
        float $igvRate = 18.00,
        string $affectation = '10',
    ): SaleDetail {
        return (new SaleDetail)
            ->setCodProducto($code)
            ->setUnidad($unit)
            ->setCantidad($quantity)
            ->setDescripcion($description)
            ->setMtoValorUnitario($unitValue)
            ->setMtoValorVenta($subtotal)
            ->setMtoBaseIgv($subtotal)
            ->setPorcentajeIgv($igvRate)
            ->setIgv($igv)
            ->setTipAfeIgv($affectation)
            ->setTotalImpuestos($igv)
            ->setMtoPrecioUnitario(round($unitValue * (1 + $igvRate / 100), 4));
    }

    protected function buildDespatch(DispatchGuide $guide): Despatch
    {
        $shipment = (new Shipment)
            ->setCodTraslado($guide->transfer_reason_code)
            ->setModTraslado($guide->transport_mode->sunatCode())
            ->setFecTraslado($guide->transfer_date->toDateTime())
            ->setPesoTotal((float) $guide->total_weight)
            ->setUndPesoTotal($guide->weight_unit)
            ->setPartida(new Direction($guide->origin_ubigeo, $guide->origin_address))
            ->setLlegada(new Direction(
                $guide->delivery_ubigeo ?? $guide->client->ubigeo ?? '150101',
                $guide->delivery_address,
            ));

        if ($guide->packages_count) {
            $shipment->setNumBultos($guide->packages_count);
        }

        if ($guide->transport_mode === \App\Enums\TransportMode::Public) {
            $shipment->setTransportista((new Transportist)
                ->setTipoDoc('6')
                ->setNumDoc($guide->carrier_ruc)
                ->setRznSocial($guide->carrier_name));

            // En transporte público SUNAT exige la fecha de entrega de bienes al
            // transportista; la mercadería se entrega el día que arranca el
            // traslado, así que se toma esa misma fecha.
            $shipment->setFecEntregaBienes($guide->transfer_date->toDateTime());
        } else {
            $shipment
                ->setVehiculo((new Vehicle)->setPlaca(str_replace('-', '', (string) $guide->vehicle_plate)))
                ->setChoferes([
                    (new Driver)
                        ->setTipo('Principal')
                        ->setTipoDoc($guide->driver_document_type ?? '1')
                        ->setNroDoc($guide->driver_document)
                        ->setLicencia($guide->driver_license)
                        ->setNombres($guide->driver_first_names)
                        ->setApellidos($guide->driver_last_names),
                ]);
        }

        // SUNAT rechaza líneas con cantidad 0: solo viajan los ítems despachados.
        $details = $guide->items
            ->filter(fn ($item) => (float) $item->quantity_dispatched > 0)
            ->map(fn ($item) => (new DespatchDetail)
                ->setCantidad((float) $item->quantity_dispatched)
                ->setUnidad($item->unit_code)
                ->setCodigo($item->product->code)
                ->setDescripcion($item->description))
            ->values()
            ->all();

        return (new Despatch)
            ->setVersion('2022')
            ->setTipoDoc('09')
            ->setSerie($guide->series->code)
            ->setCorrelativo((string) $guide->number)
            ->setFechaEmision($guide->issue_date->toDateTime())
            ->setCompany($this->company())
            ->setDestinatario($this->clientFor($guide->client))
            ->setEnvio($shipment)
            ->setDetails($details);
    }
}
