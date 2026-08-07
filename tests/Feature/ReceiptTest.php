<?php

use App\Enums\SeriesDocumentType;
use App\Models\Client;
use App\Models\Product;
use App\Models\User;
use App\Services\DispatchGuideService;
use App\Services\Facturacion\GreenterFacturacionElectronica;
use App\Services\InvoiceService;

/**
 * Boleta de venta (tipo 03): para personas con DNI y para la venta de
 * mostrador sin identificar al comprador.
 */

beforeEach(function () {
    seedSeries();

    $this->user = User::factory()->create();
    $this->product = Product::factory()->create(['stock' => 1000]);
    $this->service = app(InvoiceService::class);
});

/** Comprobante en borrador para un cliente y un total dados. */
function comprobante(Client $client, float $precio, float $cantidad, User $user, SeriesDocumentType $tipo): App\Models\Invoice
{
    $guide = draftGuide($client, [Product::query()->first()->id => $cantidad], $user);
    app(DispatchGuideService::class)->issue($guide->fresh('items'), $user);

    $invoice = app(InvoiceService::class)->createDraftFromGuideIds([$guide->id], $user, null, $tipo);
    $item = $invoice->items->first();

    return app(InvoiceService::class)->applyPricing($invoice, [$item->id => $precio], null);
}

it('emite una boleta a una persona con DNI, con su propia serie', function () {
    $persona = Client::factory()->create([
        'document_type' => '1',
        'document_number' => '45678912',
        'business_name' => 'JUAN PÉREZ GARCÍA',
    ]);

    $boleta = comprobante($persona, 100, 5, $this->user, SeriesDocumentType::Receipt);
    $this->service->applyPaymentTerms($boleta, 'contado', null, false, null);
    $this->service->issue($boleta->fresh(), $this->user);

    $boleta->refresh();

    expect($boleta->document_type)->toBe(SeriesDocumentType::Receipt)
        // La numeración es propia de la boleta, no comparte correlativo con la factura.
        ->and($boleta->full_number)->toStartWith('B001-')
        ->and($boleta->series->document_type)->toBe(SeriesDocumentType::Receipt);
});

it('manda la boleta a SUNAT como tipo 03 con el DNI del comprador', function () {
    $persona = Client::factory()->create([
        'document_type' => '1',
        'document_number' => '45678912',
        'business_name' => 'JUAN PÉREZ GARCÍA',
    ]);

    $boleta = comprobante($persona, 100, 5, $this->user, SeriesDocumentType::Receipt);
    $this->service->applyPaymentTerms($boleta, 'contado', null, false, null);
    $this->service->issue($boleta->fresh(), $this->user);

    $servicio = new GreenterFacturacionElectronica;
    $metodo = new ReflectionMethod($servicio, 'buildInvoice');
    $documento = $metodo->invoke($servicio, $boleta->fresh()->load('items.product', 'client', 'series'));

    expect($documento->getTipoDoc())->toBe('03')
        ->and($documento->getClient()->getTipoDoc())->toBe('1')
        ->and($documento->getClient()->getNumDoc())->toBe('45678912');
});

it('la boleta de mostrador va sin identificar al comprador', function () {
    $mostrador = Client::factory()->create([
        'document_type' => '0',
        'document_number' => '00000000',
        'business_name' => 'CLIENTES VARIOS',
    ]);

    // Bajo el tope que fija SUNAT.
    $boleta = comprobante($mostrador, 50, 2, $this->user, SeriesDocumentType::Receipt);
    $this->service->applyPaymentTerms($boleta, 'contado', null, false, null);
    $this->service->issue($boleta->fresh(), $this->user);

    $servicio = new GreenterFacturacionElectronica;
    $metodo = new ReflectionMethod($servicio, 'buildInvoice');
    $documento = $metodo->invoke($servicio, $boleta->fresh()->load('items.product', 'client', 'series'));

    expect($documento->getClient()->getTipoDoc())->toBe('0')
        ->and($documento->getClient()->getNumDoc())->toBe('00000000');
});

it('sobre el tope exige identificar al comprador de la boleta', function () {
    $mostrador = Client::factory()->create([
        'document_type' => '0',
        'document_number' => '00000000',
        'business_name' => 'CLIENTES VARIOS',
    ]);

    // 10 x 100 + IGV = 1180, por encima de los S/ 700.
    $boleta = comprobante($mostrador, 100, 10, $this->user, SeriesDocumentType::Receipt);
    $this->service->applyPaymentTerms($boleta, 'contado', null, false, null);

    expect(fn () => $this->service->issue($boleta->fresh(), $this->user))
        ->toThrow(InvalidArgumentException::class, 'identificar al comprador');
});

it('no deja emitir una factura a alguien que no tiene RUC', function () {
    $persona = Client::factory()->create(['document_type' => '1', 'document_number' => '45678912']);

    $factura = comprobante($persona, 100, 5, $this->user, SeriesDocumentType::Invoice);
    $this->service->applyPaymentTerms($factura, 'contado', null, false, null);

    expect(fn () => $this->service->issue($factura->fresh(), $this->user))
        ->toThrow(InvalidArgumentException::class, 'RUC');
});

it('factura y boleta llevan correlativos independientes', function () {
    $empresa = Client::factory()->create(['document_type' => '6']);
    $persona = Client::factory()->create(['document_type' => '1', 'document_number' => '45678912']);

    $factura = comprobante($empresa, 100, 5, $this->user, SeriesDocumentType::Invoice);
    $this->service->applyPaymentTerms($factura, 'contado', null, false, null);
    $this->service->issue($factura->fresh(), $this->user);

    $boleta = comprobante($persona, 100, 5, $this->user, SeriesDocumentType::Receipt);
    $this->service->applyPaymentTerms($boleta, 'contado', null, false, null);
    $this->service->issue($boleta->fresh(), $this->user);

    expect($factura->fresh()->full_number)->toStartWith('F001-')
        ->and($boleta->fresh()->full_number)->toStartWith('B001-')
        // Cada serie arranca en 1: la boleta no hereda el correlativo de la factura.
        ->and($boleta->fresh()->number)->toBe(1)
        ->and($factura->fresh()->number)->toBe(1);
});
