<?php

use App\Livewire\Invoices\Form as InvoiceForm;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\User;
use App\Services\DispatchGuideService;
use App\Services\InvoiceService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

/**
 * La factura se arma eligiendo la empresa y marcando sus guías, no digitando
 * el número: es lo que pidió el equipo tras chocar con "Guías no encontradas".
 */

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    seedSeries();

    $this->user = User::factory()->create();
    $this->user->assignRole('usuario');

    $this->client = Client::factory()->create();
    $this->product = Product::factory()->create(['stock' => 1000]);
});

function guiaEmitida(Client $client, Product $product, User $user, float $cantidad = 10): App\Models\DispatchGuide
{
    $guide = draftGuide($client, [$product->id => $cantidad], $user);
    app(DispatchGuideService::class)->issue($guide->fresh('items'), $user);

    return $guide->fresh();
}

it('lista solo las guías facturables de la empresa elegida', function () {
    $guideA = guiaEmitida($this->client, $this->product, $this->user);
    $guideB = guiaEmitida($this->client, $this->product, $this->user);

    // De otra empresa: no debe aparecer.
    $otroCliente = Client::factory()->create();
    $ajena = guiaEmitida($otroCliente, $this->product, $this->user);

    // En borrador (sin emitir): tampoco.
    $borrador = draftGuide($this->client, [$this->product->id => 5], $this->user);

    $disponibles = app(InvoiceService::class)->availableGuidesFor($this->client->id);

    expect($disponibles->pluck('id')->all())->toEqualCanonicalizing([$guideA->id, $guideB->id])
        ->and($disponibles->pluck('id'))->not->toContain($ajena->id)
        ->and($disponibles->pluck('id'))->not->toContain($borrador->id);
});

it('deja de ofrecer una guía cuando ya está en una factura activa', function () {
    $guide = guiaEmitida($this->client, $this->product, $this->user);
    $otra = guiaEmitida($this->client, $this->product, $this->user);

    app(InvoiceService::class)->createDraftFromGuideIds([$guide->id], $this->user);

    $disponibles = app(InvoiceService::class)->availableGuidesFor($this->client->id);

    expect($disponibles->pluck('id')->all())->toBe([$otra->id]);
});

it('crea el borrador con las guías marcadas y guarda el vendedor', function () {
    $guideA = guiaEmitida($this->client, $this->product, $this->user, 10);
    $guideB = guiaEmitida($this->client, $this->product, $this->user, 4);

    $vendedor = User::factory()->create(['name' => 'Nathaly Bravo']);

    Livewire::actingAs($this->user)
        ->test(InvoiceForm::class, ['invoice' => null])
        ->set('clientId', $this->client->id)
        ->set('sellerId', $vendedor->id)
        ->set('selectedGuides', [$guideA->id, $guideB->id])
        ->call('createDraft')
        ->assertHasNoErrors();

    $invoice = Invoice::query()->latest('id')->first();

    expect($invoice->dispatchGuides)->toHaveCount(2)
        ->and($invoice->client_id)->toBe($this->client->id)
        ->and($invoice->seller->name)->toBe('Nathaly Bravo')
        // Los dos despachos del mismo producto se consolidan en una línea.
        ->and($invoice->items)->toHaveCount(1)
        ->and((float) $invoice->items->first()->quantity)->toBe(14.0);
});

it('exige marcar al menos una guía', function () {
    guiaEmitida($this->client, $this->product, $this->user);

    Livewire::actingAs($this->user)
        ->test(InvoiceForm::class, ['invoice' => null])
        ->set('clientId', $this->client->id)
        ->set('selectedGuides', [])
        ->call('createDraft')
        ->assertHasErrors('selectedGuides');

    expect(Invoice::query()->count())->toBe(0);
});

it('cambiar de empresa limpia las guías marcadas', function () {
    $guide = guiaEmitida($this->client, $this->product, $this->user);
    $otroCliente = Client::factory()->create();

    Livewire::actingAs($this->user)
        ->test(InvoiceForm::class, ['invoice' => null])
        ->set('clientId', $this->client->id)
        ->set('selectedGuides', [$guide->id])
        ->set('clientId', $otroCliente->id)
        ->assertSet('selectedGuides', []);
});

it('agrega más guías al borrador desde el mismo selector', function () {
    $guideA = guiaEmitida($this->client, $this->product, $this->user, 10);
    $guideB = guiaEmitida($this->client, $this->product, $this->user, 6);

    $invoice = app(InvoiceService::class)->createDraftFromGuideIds([$guideA->id], $this->user);

    Livewire::actingAs($this->user)
        ->test(InvoiceForm::class, ['invoice' => $invoice])
        ->set('selectedGuides', [$guideB->id])
        ->call('addSelectedGuides')
        ->assertHasNoErrors();

    expect($invoice->fresh()->dispatchGuides)->toHaveCount(2)
        ->and((float) $invoice->fresh()->items->first()->quantity)->toBe(16.0);
});

it('no permite mezclar guías de clientes distintos en una factura', function () {
    $guide = guiaEmitida($this->client, $this->product, $this->user);
    $ajena = guiaEmitida(Client::factory()->create(), $this->product, $this->user);

    expect(fn () => app(InvoiceService::class)->createDraftFromGuideIds([$guide->id, $ajena->id], $this->user))
        ->toThrow(InvalidArgumentException::class, 'mismo cliente');
});

it('respeta "Sin vendedor" en vez de poner al que registra', function () {
    $guide = guiaEmitida($this->client, $this->product, $this->user);

    Livewire::actingAs($this->user)
        ->test(InvoiceForm::class, ['invoice' => null])
        ->set('clientId', $this->client->id)
        ->set('sellerId', null)
        ->set('selectedGuides', [$guide->id])
        ->call('createDraft')
        ->assertHasNoErrors();

    expect(Invoice::query()->latest('id')->first()->seller_id)->toBeNull();
});

it('rechaza guías que no son de la empresa elegida en pantalla', function () {
    $ajena = guiaEmitida(Client::factory()->create(), $this->product, $this->user);

    // Los ids llegan del navegador: aunque se manipulen, manda la empresa elegida.
    Livewire::actingAs($this->user)
        ->test(InvoiceForm::class, ['invoice' => null])
        ->set('clientId', $this->client->id)
        ->set('selectedGuides', [$ajena->id])
        ->call('createDraft')
        ->assertHasErrors('selectedGuides');

    expect(Invoice::query()->count())->toBe(0);
});

it('suelta la orden de compra al retirar la guía que la traía', function () {
    $conOc = guiaEmitida($this->client, $this->product, $this->user);
    $sinOc = guiaEmitida($this->client, $this->product, $this->user);

    $oc = App\Models\PurchaseOrder::query()->create([
        'client_id' => $this->client->id,
        'origin' => App\Enums\PurchaseOrderOrigin::Received,
        'number' => 'OC-2026-77',
        'date' => now()->toDateString(),
        'amount' => 1000,
        'created_by' => $this->user->id,
    ]);
    $conOc->update(['purchase_order_id' => $oc->id]);

    $invoice = app(InvoiceService::class)->createDraftFromGuideIds([$sinOc->id], $this->user);
    app(InvoiceService::class)->addGuideIds($invoice, [$conOc->id]);

    expect($invoice->fresh()->purchase_order_id)->toBe($oc->id);

    app(InvoiceService::class)->removeGuide($invoice->fresh(), $conOc->fresh());

    expect($invoice->fresh()->purchase_order_id)->toBeNull();
});

it('descarta de la selección las guías que otro ya facturó', function () {
    $mia = guiaEmitida($this->client, $this->product, $this->user);
    $robada = guiaEmitida($this->client, $this->product, $this->user);

    $componente = Livewire::actingAs($this->user)
        ->test(InvoiceForm::class, ['invoice' => null])
        ->set('clientId', $this->client->id)
        ->set('selectedGuides', [$mia->id, $robada->id]);

    // Mientras la pantalla estaba abierta, otro la metió en su factura.
    app(InvoiceService::class)->createDraftFromGuideIds([$robada->id], $this->user);

    $componente->call('createDraft')->assertHasErrors('selectedGuides');

    // La guía que ya no se puede facturar sale de la selección, así el
    // usuario puede reintentar sin quedar atascado con una marca invisible.
    $componente->assertSet('selectedGuides', [$mia->id]);
});
