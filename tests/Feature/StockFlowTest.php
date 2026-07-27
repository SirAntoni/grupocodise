<?php

use App\Enums\DispatchGuideStatus;
use App\Enums\RequirementStatus;
use App\Enums\StockMovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\Client;
use App\Models\Product;
use App\Models\Series;
use App\Models\StockMovement;
use App\Services\DispatchGuideService;
use App\Services\StockService;

beforeEach(function () {
    seedSeries();
    $this->user = testUser();
    $this->client = Client::factory()->create();
    $this->cemento = Product::factory()->create(['stock' => 100]);
    $this->fierro = Product::factory()->create(['stock' => 50]);
});

it('descuenta el stock por la cantidad despachada al emitir la guía', function () {
    $guide = draftGuide($this->client, [$this->cemento->id => 30, $this->fierro->id => 10], $this->user);

    // La cantidad despachada difiere de la solicitada.
    $guide->items()->where('product_id', $this->cemento->id)->update(['quantity_dispatched' => 25]);

    $guide = app(DispatchGuideService::class)->issue($guide, $this->user);

    expect($guide->status)->toBe(DispatchGuideStatus::Issued)
        ->and($guide->full_number)->toBe('T001-00000001')
        ->and((float) $this->cemento->fresh()->stock)->toBe(75.0)
        ->and((float) $this->fierro->fresh()->stock)->toBe(40.0)
        ->and($guide->requirement->fresh()->status)->toBe(RequirementStatus::Dispatched);

    $movement = StockMovement::query()->where('product_id', $this->cemento->id)->first();
    expect($movement->type)->toBe(StockMovementType::DispatchExit)
        ->and((float) $movement->quantity)->toBe(25.0)
        ->and((float) $movement->stock_before)->toBe(100.0)
        ->and((float) $movement->stock_after)->toBe(75.0);
});

it('restituye el stock al anular una guía emitida y deja rastro en el kardex', function () {
    $service = app(DispatchGuideService::class);
    $guide = draftGuide($this->client, [$this->cemento->id => 30], $this->user);
    $guide = $service->issue($guide, $this->user);

    expect((float) $this->cemento->fresh()->stock)->toBe(70.0);

    $service->annul($guide, 'Cliente canceló el pedido', $this->user);

    expect((float) $this->cemento->fresh()->stock)->toBe(100.0)
        ->and($guide->fresh()->status)->toBe(DispatchGuideStatus::Annulled)
        ->and($guide->fresh()->annulment_reason)->toBe('Cliente canceló el pedido')
        ->and(StockMovement::query()->where('type', StockMovementType::AnnulmentRestitution)->count())->toBe(1);
});

it('bloquea la emisión cuando el stock es insuficiente sin consumir correlativo', function () {
    $guide = draftGuide($this->client, [$this->cemento->id => 500], $this->user);

    expect(fn () => app(DispatchGuideService::class)->issue($guide, $this->user))
        ->toThrow(InsufficientStockException::class);

    expect($guide->fresh()->status)->toBe(DispatchGuideStatus::Draft)
        ->and($guide->fresh()->full_number)->toBeNull()
        ->and((float) $this->cemento->fresh()->stock)->toBe(100.0)
        ->and(Series::query()->where('document_type', 'guia_remision')->first()->next_number)->toBe(1);
});

it('una doble anulación no restituye el stock dos veces', function () {
    $service = app(DispatchGuideService::class);
    $guide = $service->issue(draftGuide($this->client, [$this->cemento->id => 30], $this->user), $this->user);

    $service->annul($guide, 'Primera anulación', $this->user);

    expect(fn () => $service->annul($guide->fresh(), 'Segunda anulación', $this->user))
        ->toThrow(InvalidArgumentException::class);

    expect((float) $this->cemento->fresh()->stock)->toBe(100.0);
});

it('una guía emitida no puede volver a emitirse', function () {
    $service = app(DispatchGuideService::class);
    $guide = $service->issue(draftGuide($this->client, [$this->cemento->id => 10], $this->user), $this->user);

    expect(fn () => $service->issue($guide->fresh(), $this->user))
        ->toThrow(InvalidArgumentException::class);

    expect((float) $this->cemento->fresh()->stock)->toBe(90.0);
});

it('registra entradas y ajustes manuales en el kardex', function () {
    $stock = app(StockService::class);

    $stock->registerEntry($this->cemento, 50, 'Compra a proveedor', $this->user);
    expect((float) $this->cemento->fresh()->stock)->toBe(150.0);

    $stock->registerAdjustment($this->cemento, -20, 'Merma por rotura', $this->user);
    expect((float) $this->cemento->fresh()->stock)->toBe(130.0);

    // Un ajuste no puede dejar stock negativo.
    expect(fn () => $stock->registerAdjustment($this->cemento, -999, 'Error', $this->user))
        ->toThrow(InsufficientStockException::class);
});

it('la numeración de guías es correlativa por serie', function () {
    $service = app(DispatchGuideService::class);

    $first = $service->issue(draftGuide($this->client, [$this->cemento->id => 1], $this->user), $this->user);
    $second = $service->issue(draftGuide($this->client, [$this->cemento->id => 1], $this->user), $this->user);

    expect($first->full_number)->toBe('T001-00000001')
        ->and($second->full_number)->toBe('T001-00000002');
});
