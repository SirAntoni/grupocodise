<?php

use App\Enums\PaymentMethod;
use App\Enums\ReceivableStatus;
use App\Enums\TrafficLight;
use App\Models\AccountReceivable;
use App\Models\Client;
use App\Models\Invoice;
use App\Services\ReceivableService;
use Illuminate\Support\Carbon;

it('calcula el semáforo según el vencimiento', function (int $daysFromToday, TrafficLight $expected) {
    $due = Carbon::today()->addDays($daysFromToday);

    expect(TrafficLight::forDueDate($due))->toBe($expected);
})->with([
    'vencida ayer es rojo' => [-1, TrafficLight::Red],
    'vence hoy es amarillo' => [0, TrafficLight::Yellow],
    'vence en 5 días es amarillo' => [5, TrafficLight::Yellow],
    'vence en 6 días es verde' => [6, TrafficLight::Green],
    'vence en 30 días es verde' => [30, TrafficLight::Green],
]);

it('el recálculo diario actualiza el color de las cuentas abiertas', function () {
    $user = testUser();
    $client = Client::factory()->create();

    $invoice = Invoice::query()->create([
        'client_id' => $client->id,
        'status' => 'aceptada',
        'issue_date' => now()->subDays(28),
        'due_date' => now()->addDays(2),
        'taxable_amount' => 100,
        'igv' => 18,
        'total' => 118,
        'created_by' => $user->id,
    ]);

    // Guardada como verde (estado desactualizado a propósito).
    $receivable = AccountReceivable::query()->create([
        'invoice_id' => $invoice->id,
        'due_date' => $invoice->due_date,
        'amount' => 118,
        'paid_amount' => 0,
        'balance' => 118,
        'traffic_light' => TrafficLight::Green,
        'status' => ReceivableStatus::Pending,
    ]);

    $updated = app(ReceivableService::class)->refreshTrafficLights();

    expect($updated)->toBe(1)
        ->and($receivable->fresh()->traffic_light)->toBe(TrafficLight::Yellow);
});

it('registra pagos parciales y totales actualizando saldo y estado', function () {
    $user = testUser();
    $client = Client::factory()->create();

    $invoice = Invoice::query()->create([
        'client_id' => $client->id,
        'status' => 'aceptada',
        'issue_date' => now(),
        'due_date' => now()->addDays(30),
        'taxable_amount' => 1000,
        'igv' => 180,
        'total' => 1180,
        'created_by' => $user->id,
    ]);

    $service = app(ReceivableService::class);
    $receivable = $service->createForInvoice($invoice);

    $service->registerPayment($receivable, 500, now()->toDateString(), PaymentMethod::Transfer->value, 'OP-123', null, $user);
    $receivable->refresh();
    expect((float) $receivable->balance)->toBe(680.0)
        ->and($receivable->status)->toBe(ReceivableStatus::Partial);

    // No se puede pagar más que el saldo.
    expect(fn () => $service->registerPayment($receivable, 9999, now()->toDateString(), 'efectivo', null, null, $user))
        ->toThrow(InvalidArgumentException::class, 'excede el saldo');

    $service->registerPayment($receivable, 680, now()->toDateString(), 'deposito', null, null, $user);
    $receivable->refresh();
    expect((float) $receivable->balance)->toBe(0.0)
        ->and($receivable->status)->toBe(ReceivableStatus::Paid);
});

it('anular un pago recalcula el saldo y exige motivo', function () {
    $user = testUser();
    $client = Client::factory()->create();

    $invoice = Invoice::query()->create([
        'client_id' => $client->id,
        'status' => 'aceptada',
        'issue_date' => now(),
        'due_date' => now()->addDays(30),
        'taxable_amount' => 100,
        'igv' => 18,
        'total' => 118,
        'created_by' => $user->id,
    ]);

    $service = app(ReceivableService::class);
    $receivable = $service->createForInvoice($invoice);
    $payment = $service->registerPayment($receivable, 118, now()->toDateString(), 'efectivo', null, null, $user);

    expect($receivable->fresh()->status)->toBe(ReceivableStatus::Paid);

    $service->deletePayment($payment, 'Registrado por error', $user);

    $receivable->refresh();
    $annulled = \App\Models\Payment::withTrashed()->find($payment->id);

    expect((float) $receivable->balance)->toBe(118.0)
        ->and($receivable->status)->toBe(ReceivableStatus::Pending)
        ->and($annulled->deleted_at)->not->toBeNull()
        ->and($annulled->deletion_reason)->toBe('Registrado por error')
        ->and($annulled->deleted_by)->toBe($user->id);
});
