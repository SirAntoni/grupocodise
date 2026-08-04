<?php

use App\Livewire\Requirements\Form as RequirementForm;
use App\Models\Client;
use App\Models\DispatchGuide;
use App\Models\ElectronicDocument;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\Requirement;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\DispatchGuideService;
use App\Services\Facturacion\SunatResult;
use App\Services\FacturacionElectronica;
use App\Services\QuotationService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

/**
 * Regresiones de límites del esquema. Estos casos solo fallan sobre MySQL:
 * SQLite ignora el largo de los varchar, los CHECK y el rango de los enteros,
 * y por eso dejó pasar a producción los errores 500 del 4 de agosto de 2026.
 */

it('crea requerimientos sin desbordar la columna de código', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('admin');

    $client = Client::factory()->create();
    $product = Product::factory()->create(['stock' => 100]);

    // El marcador provisional tiene que caber en `code` (varchar 20).
    expect(strlen(Requirement::temporaryCode()))->toBeLessThanOrEqual(20);

    Livewire::actingAs($user)->test(RequirementForm::class, ['requirement' => null])
        ->set('client_id', $client->id)
        ->set('crew_chief', 'Jefe de cuadrilla')
        ->set('district', 'San Juan de Lurigancho')
        ->set('delivery_address', 'Obra Av. Perú 123')
        ->set('required_date', now()->addDay()->toDateString())
        ->set('items', [['key' => 'item-1', 'product_id' => $product->id, 'quantity' => 10]])
        ->call('save')
        ->assertHasNoErrors();

    // El número exacto depende del autoincremental (MySQL no lo revierte entre
    // tests); lo que importa es que el código definitivo reemplazó al temporal.
    expect(Requirement::query()->count())->toBe(1)
        ->and(Requirement::query()->value('code'))->toMatch('/^REQ-\d{5}$/');
});

it('crea cotizaciones sin desbordar la columna de código', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create();
    $product = Product::factory()->create();

    expect(strlen(Quotation::temporaryCode()))->toBeLessThanOrEqual(20);

    $quotation = app(QuotationService::class)->save(null, [
        'client_id' => $client->id,
        'issue_date' => now()->toDateString(),
        'valid_until' => now()->addDays(15)->toDateString(),
        'notes' => null,
    ], [[
        'product_id' => $product->id,
        'description' => $product->name,
        'unit_code' => $product->unit_code,
        'quantity' => 2,
        'unit_value' => 50,
    ]], $user);

    expect($quotation->code)->toMatch('/^COT-\d{5}$/');
});

it('emite una guía con ítems solicitados pero no despachados', function () {
    seedSeries();

    $user = User::factory()->create();
    $client = Client::factory()->create();
    $despachado = Product::factory()->create(['stock' => 200]);
    $noDespachado = Product::factory()->create(['stock' => 500]);

    $guide = draftGuide($client, [$despachado->id => 100, $noDespachado->id => 500], $user);

    // Caso corriente: llegó el cemento, no llegaron los ladrillos.
    $guide->items()->where('product_id', $noDespachado->id)->update(['quantity_dispatched' => 0]);

    app(DispatchGuideService::class)->issue($guide->fresh('items'), $user);

    // El ítem no despachado no mueve stock ni deja rastro en el kardex.
    expect($despachado->fresh()->stock)->toEqual(100.0)
        ->and($noDespachado->fresh()->stock)->toEqual(500.0)
        ->and(StockMovement::query()->where('product_id', $noDespachado->id)->count())->toBe(0);

    // Y al anular se restituye solo lo que salió.
    app(DispatchGuideService::class)->annul($guide->fresh(), 'Prueba de restitución', $user);

    expect($despachado->fresh()->stock)->toEqual(200.0)
        ->and($noDespachado->fresh()->stock)->toEqual(500.0);
});

it('guarda el rechazo de SUNAT aunque el código venga larguísimo', function () {
    seedSeries();

    $user = User::factory()->create();
    $client = Client::factory()->create();
    $product = Product::factory()->create(['stock' => 100]);

    $guide = draftGuide($client, [$product->id => 10], $user);
    app(DispatchGuideService::class)->issue($guide->fresh('items'), $user);

    // Greenter arma el código concatenando los dígitos del mensaje cuando el
    // fault de SUNAT no trae uno: sale mucho más largo que la columna.
    $codigoLargo = '20161515648'.str_repeat('0100000001', 3);
    expect(strlen($codigoLargo))->toBeGreaterThan(20);

    $this->mock(FacturacionElectronica::class, function ($mock) use ($codigoLargo) {
        $mock->shouldReceive('sendDispatchGuide')->andReturn(
            SunatResult::rejected($codigoLargo, 'El comprobante fue rechazado por SUNAT.')
        );
        $mock->shouldReceive('checkDispatchGuideStatus')->andReturn(
            SunatResult::rejected($codigoLargo, 'El comprobante fue rechazado por SUNAT.')
        );
    });

    $document = ElectronicDocument::query()
        ->where('documentable_type', (new DispatchGuide)->getMorphClass())
        ->firstOrFail();
    // El driver fake ya la dio por aceptada al emitir: se devuelve a pendiente
    // para que el job vuelva a hablar con SUNAT (aquí, el mock que rechaza).
    $document->update(['ticket' => null, 'sunat_status' => \App\Enums\SunatStatus::Pending]);

    app(\App\Jobs\SubmitElectronicDocument::class, ['document' => $document])->handle(
        app(FacturacionElectronica::class),
        app(\App\Services\InvoiceService::class),
        app(\App\Services\CreditNoteService::class),
    );

    $document->refresh();

    expect(strlen((string) $document->error_code))->toBeLessThanOrEqual(20)
        ->and($document->error_message)->toMatch('/rechazado/')
        ->and($document->logs()->first()->response_code)->not->toBeNull();
});
