<?php

use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Product;
use App\Models\User;
use App\Services\DispatchGuideService;
use App\Services\Facturacion\GreenterFacturacionElectronica;
use App\Services\InvoiceService;

/**
 * Condiciones de pago por factura y detracción del SPOT: lo que el cliente
 * maneja hoy en su ERP anterior y el sistema no cubría.
 */

beforeEach(function () {
    seedSeries();

    $this->user = User::factory()->create();
    $this->client = Client::factory()->create();
    $this->product = Product::factory()->create(['stock' => 1000]);
    $this->service = app(InvoiceService::class);
});

/** Factura en borrador con un total conocido. */
function facturaCon(float $precio, float $cantidad, Client $client, Product $product, User $user): App\Models\Invoice
{
    $guide = draftGuide($client, [$product->id => $cantidad], $user);
    app(DispatchGuideService::class)->issue($guide->fresh('items'), $user);

    $invoice = app(InvoiceService::class)->createDraftFromGuideIds([$guide->id], $user);
    $item = $invoice->items->first();

    return app(InvoiceService::class)->applyPricing($invoice, [$item->id => $precio], null);
}

it('calcula el vencimiento con los días de crédito de cada factura', function () {
    $invoice = facturaCon(100, 10, $this->client, $this->product, $this->user);

    $this->service->applyPaymentTerms($invoice, 'credito', 45, false, null);
    $this->service->issue($invoice->fresh(), $this->user);

    $invoice->refresh();

    expect($invoice->credit_days)->toBe(45)
        ->and($invoice->due_date->toDateString())->toBe($invoice->issue_date->copy()->addDays(45)->toDateString());
});

it('al contado vence el mismo día y no genera cuenta por cobrar', function () {
    $invoice = facturaCon(100, 10, $this->client, $this->product, $this->user);

    $this->service->applyPaymentTerms($invoice, 'contado', null, false, null);
    $this->service->issue($invoice->fresh(), $this->user);

    $invoice->refresh();
    expect($invoice->due_date->toDateString())->toBe($invoice->issue_date->toDateString())
        ->and($invoice->credit_days)->toBeNull();

    // El driver fake la da por aceptada al emitir.
    $this->service->markAccepted($invoice->fresh());

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Accepted)
        ->and($invoice->fresh()->receivable)->toBeNull();
});

it('al crédito sí genera la cuenta por cobrar', function () {
    $invoice = facturaCon(100, 10, $this->client, $this->product, $this->user);

    $this->service->applyPaymentTerms($invoice, 'credito', 30, false, null);
    $this->service->issue($invoice->fresh(), $this->user);
    $this->service->markAccepted($invoice->fresh());

    expect($invoice->fresh()->receivable)->not->toBeNull()
        ->and((float) $invoice->fresh()->receivable->amount)->toBe((float) $invoice->fresh()->total);
});

it('calcula la detracción al 12% y la redondea a soles enteros', function () {
    // 10 x 156.00 = 1560.00 + IGV = 1840.80, igual que la factura real del cliente.
    $invoice = facturaCon(156, 10, $this->client, $this->product, $this->user);

    expect((float) $invoice->total)->toBe(1840.80);

    $this->service->applyPaymentTerms($invoice, 'credito', 30, true, '037');
    $invoice->refresh();

    expect((float) $invoice->detraction_percent)->toBe(12.0)
        // 1840.80 x 12% = 220.896 → se deposita en soles enteros.
        ->and((float) $invoice->detraction_amount)->toBe(221.0)
        ->and($invoice->detraction_code)->toBe('037');
});

it('el porcentaje sale del bien elegido, no está fijo', function () {
    $invoice = facturaCon(156, 10, $this->client, $this->product, $this->user);

    // 009 Arena y piedra va al 10%, como en la factura anterior del cliente.
    $this->service->applyPaymentTerms($invoice, 'credito', 30, true, '009');

    expect((float) $invoice->fresh()->detraction_percent)->toBe(10.0)
        ->and((float) $invoice->fresh()->detraction_amount)->toBe(184.0);
});

it('la detracción sigue al total cuando cambian los precios', function () {
    $invoice = facturaCon(156, 10, $this->client, $this->product, $this->user);
    $this->service->applyPaymentTerms($invoice, 'credito', 30, true, '037');

    expect((float) $invoice->fresh()->detraction_amount)->toBe(221.0);

    // Se corrige el precio a la mitad antes de emitir.
    $item = $invoice->fresh()->items->first();
    $this->service->applyPricing($invoice->fresh(), [$item->id => 78], null);

    expect((float) $invoice->fresh()->total)->toBe(920.40)
        ->and((float) $invoice->fresh()->detraction_amount)->toBe(110.0);
});

it('declara la detracción en el comprobante que se manda a SUNAT', function () {
    $invoice = facturaCon(156, 10, $this->client, $this->product, $this->user);
    $this->service->applyPaymentTerms($invoice, 'credito', 30, true, '037');
    $this->service->issue($invoice->fresh(), $this->user);

    $servicio = new GreenterFacturacionElectronica;
    $metodo = new ReflectionMethod($servicio, 'buildInvoice');
    $documento = $metodo->invoke($servicio, $invoice->fresh()->load('items.product', 'client', 'series'));

    $detraccion = $documento->getDetraccion();

    expect($detraccion)->not->toBeNull()
        ->and($detraccion->getCodBienDetraccion())->toBe('037')
        ->and($detraccion->getPercent())->toBe(12.0)
        ->and($detraccion->getMount())->toBe(221.0)
        ->and($detraccion->getCtaBanco())->toBe(config('facturacion.detraccion.cuenta_banco_nacion'))
        // Catálogo 51: la operación deja de ser venta interna común.
        ->and($documento->getTipoOperacion())->toBe('1001')
        // Y lleva la leyenda obligatoria del SPOT.
        ->and(collect($documento->getLegends())->map(fn ($l) => $l->getCode())->all())->toContain('2006');

    // La cuota al crédito es por lo que el cliente realmente transfiere.
    expect($documento->getCuotas()[0]->getMonto())->toBe(round(1840.80 - 221, 2));
});

it('sin detracción el comprobante sale como venta interna normal', function () {
    $invoice = facturaCon(100, 10, $this->client, $this->product, $this->user);
    $this->service->applyPaymentTerms($invoice, 'contado', null, false, null);
    $this->service->issue($invoice->fresh(), $this->user);

    $servicio = new GreenterFacturacionElectronica;
    $metodo = new ReflectionMethod($servicio, 'buildInvoice');
    $documento = $metodo->invoke($servicio, $invoice->fresh()->load('items.product', 'client', 'series'));

    expect($documento->getDetraccion())->toBeNull()
        ->and($documento->getTipoOperacion())->toBe('0101')
        ->and($documento->getFormaPago()->getTipo())->toBe('Contado');
});

it('exige los días cuando la venta es al crédito', function () {
    $invoice = facturaCon(100, 10, $this->client, $this->product, $this->user);

    expect(fn () => $this->service->applyPaymentTerms($invoice, 'credito', null, false, null))
        ->toThrow(InvalidArgumentException::class, 'días');
});

it('la cuenta por cobrar puede llevarse por el neto si así se configura', function () {
    config(['facturacion.detraccion.cuenta_por_cobrar_por' => 'neto']);

    $invoice = facturaCon(156, 10, $this->client, $this->product, $this->user);
    $this->service->applyPaymentTerms($invoice, 'credito', 30, true, '037');
    $this->service->issue($invoice->fresh(), $this->user);
    $this->service->markAccepted($invoice->fresh());

    // 1840.80 − 221 = 1619.80, que es lo que el cliente transfiere.
    expect((float) $invoice->fresh()->receivable->amount)->toBe(1619.80);
});
