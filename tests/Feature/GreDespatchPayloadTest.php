<?php

use App\Enums\TransportMode;
use App\Models\Client;
use App\Models\Product;
use App\Models\User;
use App\Services\Facturacion\GreenterFacturacionElectronica;

/**
 * Lo que se le manda a SUNAT en una guía de remisión electrónica. El API del
 * GRE rechazó la primera guía real por un campo que no estábamos enviando.
 */

function despatchDe(string $modo): Greenter\Model\Despatch\Despatch
{
    seedSeries();

    $user = User::factory()->create();
    $client = Client::factory()->create();
    $product = Product::factory()->create(['stock' => 100]);

    $guide = draftGuide($client, [$product->id => 10], $user);
    $guide->update([
        'transport_mode' => $modo,
        'carrier_ruc' => '20616044037',
        'carrier_name' => 'TRANSPORTES DE PRUEBA S.A.C.',
        'transfer_date' => now()->addDays(5)->toDateString(),
    ]);
    $guide->items()->update(['quantity_dispatched' => 10]);

    // La serie y el número se asignan al emitir, y el payload los necesita.
    app(App\Services\DispatchGuideService::class)->issue($guide->fresh('items'), $user);

    $servicio = new GreenterFacturacionElectronica;
    $metodo = new ReflectionMethod($servicio, 'buildDespatch');

    return $metodo->invoke($servicio, $guide->fresh(['items.product', 'client', 'series']));
}

it('en transporte público envía la fecha de entrega de bienes al transportista', function () {
    $despatch = despatchDe(TransportMode::Public->value);
    $envio = $despatch->getEnvio();

    expect($envio->getFecEntregaBienes())->not->toBeNull()
        ->and($envio->getFecEntregaBienes()->format('Y-m-d'))->toBe($envio->getFecTraslado()->format('Y-m-d'))
        ->and($envio->getTransportista()->getNumDoc())->toBe('20616044037');
});

it('en transporte privado manda el vehículo y el chofer, no el transportista', function () {
    $despatch = despatchDe(TransportMode::Private->value);
    $envio = $despatch->getEnvio();

    expect($envio->getTransportista())->toBeNull()
        ->and($envio->getVehiculo()->getPlaca())->toBe('ABC123')
        ->and($envio->getChoferes())->toHaveCount(1);
});
