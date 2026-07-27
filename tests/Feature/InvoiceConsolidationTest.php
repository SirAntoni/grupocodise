<?php

use App\Enums\InvoiceItemType;
use App\Enums\InvoiceStatus;
use App\Enums\ReceivableStatus;
use App\Jobs\SubmitElectronicDocument;
use App\Models\Client;
use App\Models\Product;
use App\Services\CreditNoteService;
use App\Services\DispatchGuideService;
use App\Services\InvoiceService;

beforeEach(function () {
    seedSeries();
    $this->user = testUser();
    $this->client = Client::factory()->create();
    $this->cemento = Product::factory()->create(['stock' => 1000]);
    $this->ladrillo = Product::factory()->create(['stock' => 1000]);

    $guides = app(DispatchGuideService::class);
    $this->guideA = $guides->issue(draftGuide($this->client, [$this->cemento->id => 10, $this->ladrillo->id => 100], $this->user), $this->user);
    $this->guideB = $guides->issue(draftGuide($this->client, [$this->cemento->id => 5], $this->user), $this->user);
});

it('consolida los ítems de varias guías sumando cantidades por producto', function () {
    $invoice = app(InvoiceService::class)->createDraftFromGuideNumbers(
        [$this->guideA->full_number, $this->guideB->full_number],
        $this->user,
    );

    expect($invoice->items)->toHaveCount(2);

    $cementoLine = $invoice->items->firstWhere('product_id', $this->cemento->id);
    $ladrilloLine = $invoice->items->firstWhere('product_id', $this->ladrillo->id);

    expect((float) $cementoLine->quantity)->toBe(15.0)
        ->and((float) $ladrilloLine->quantity)->toBe(100.0)
        ->and($invoice->dispatchGuides)->toHaveCount(2);
});

it('calcula subtotal, IGV 18% y total con precios editables y zona lejana', function () {
    $service = app(InvoiceService::class);
    $invoice = $service->createDraftFromGuideNumbers([$this->guideB->full_number], $this->user);

    $line = $invoice->items->firstWhere('product_id', $this->cemento->id);
    $invoice = $service->applyPricing($invoice, [$line->id => 20.00], 50.00);

    // 5 × 20 = 100 productos + 50 zona lejana = 150; IGV 27; total 177.
    expect((float) $invoice->taxable_amount)->toBe(150.0)
        ->and((float) $invoice->igv)->toBe(27.0)
        ->and((float) $invoice->total)->toBe(177.0)
        ->and($invoice->items()->where('type', InvoiceItemType::RemoteZone)->count())->toBe(1);

    // Quitar la zona lejana recalcula.
    $invoice = $service->applyPricing($invoice, [$line->id => 20.00], null);
    expect((float) $invoice->total)->toBe(118.0)
        ->and($invoice->items()->where('type', InvoiceItemType::RemoteZone)->count())->toBe(0);
});

it('no emite facturas con ítems de producto sin precio', function () {
    $service = app(InvoiceService::class);
    $invoice = $service->createDraftFromGuideNumbers([$this->guideA->full_number], $this->user);

    // Solo se digita el precio de uno de los dos productos.
    $line = $invoice->items->firstWhere('product_id', $this->cemento->id);
    $invoice = $service->applyPricing($invoice, [$line->id => 20.00], null);

    expect(fn () => $service->issue($invoice, $this->user))
        ->toThrow(InvalidArgumentException::class, 'sin precio');
});

it('excluye de la consolidación los productos con cantidad despachada cero', function () {
    $guides = app(DispatchGuideService::class);
    $draft = draftGuide($this->client, [$this->cemento->id => 10, $this->ladrillo->id => 50], $this->user);
    // El ladrillo se solicitó pero no se despachó.
    $draft->items()->where('product_id', $this->ladrillo->id)->update(['quantity_dispatched' => 0]);
    $guide = $guides->issue($draft, $this->user);

    $invoice = app(InvoiceService::class)->createDraftFromGuideNumbers([$guide->full_number], $this->user);

    expect($invoice->items)->toHaveCount(1)
        ->and($invoice->items->first()->product_id)->toBe($this->cemento->id);
});

it('impide que una guía esté en dos facturas activas', function () {
    $service = app(InvoiceService::class);
    $service->createDraftFromGuideNumbers([$this->guideA->full_number], $this->user);

    expect(fn () => $service->createDraftFromGuideNumbers([$this->guideA->full_number], $this->user))
        ->toThrow(InvalidArgumentException::class, 'ya está en la factura');
});

it('rechaza consolidar guías de clientes distintos', function () {
    $otherClient = Client::factory()->create();
    $foreignGuide = app(DispatchGuideService::class)->issue(
        draftGuide($otherClient, [$this->cemento->id => 1], $this->user),
        $this->user,
    );

    expect(fn () => app(InvoiceService::class)->createDraftFromGuideNumbers(
        [$this->guideA->full_number, $foreignGuide->full_number],
        $this->user,
    ))->toThrow(InvalidArgumentException::class, 'mismo cliente');
});

it('emite la factura, la acepta SUNAT (fake) y crea la cuenta por cobrar a 30 días', function () {
    $service = app(InvoiceService::class);
    $invoice = $service->createDraftFromGuideNumbers([$this->guideB->full_number], $this->user);
    $line = $invoice->items->first();
    $invoice = $service->applyPricing($invoice, [$line->id => 20.00], null);

    $invoice = $service->issue($invoice, $this->user);
    expect($invoice->full_number)->toBe('F001-00000001')
        ->and($invoice->status)->toBe(InvoiceStatus::PendingSubmission)
        ->and($invoice->due_date->toDateString())->toBe(now()->addDays(30)->toDateString());

    SubmitElectronicDocument::dispatchSync($invoice->electronicDocument()->first());

    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::Accepted)
        ->and($invoice->receivable)->not->toBeNull()
        ->and((float) $invoice->receivable->balance)->toBe(118.0)
        ->and($invoice->receivable->due_date->toDateString())->toBe(now()->addDays(30)->toDateString());
});

it('anula con nota de crédito por el total y libera las guías para refacturar', function () {
    $service = app(InvoiceService::class);
    $invoice = $service->createDraftFromGuideNumbers([$this->guideB->full_number], $this->user);
    $invoice = $service->applyPricing($invoice, [$invoice->items->first()->id => 20.00], null);
    $invoice = $service->issue($invoice, $this->user);
    SubmitElectronicDocument::dispatchSync($invoice->electronicDocument()->first());

    $creditNote = app(CreditNoteService::class)->annulInvoice($invoice->refresh(), 'Error en la operación', $this->user);
    SubmitElectronicDocument::dispatchSync($creditNote->electronicDocument()->first());

    $invoice->refresh();
    expect($creditNote->fresh()->full_number)->toBe('FC01-00000001')
        ->and((float) $creditNote->total)->toBe((float) $invoice->total)
        ->and($invoice->status)->toBe(InvoiceStatus::Annulled)
        ->and($invoice->receivable->fresh()->status)->toBe(ReceivableStatus::Annulled)
        ->and($this->guideB->fresh()->activeInvoice())->toBeNull();

    // La guía liberada puede facturarse de nuevo.
    $again = $service->createDraftFromGuideNumbers([$this->guideB->full_number], $this->user);
    expect($again->items)->toHaveCount(1);
});
