<?php

use App\Models\Client;
use App\Models\Product;
use App\Models\User;
use App\Services\DispatchGuideService;
use App\Services\ReportService;
use Database\Seeders\RolesAndPermissionsSeeder;

/**
 * El reporte de guías debe poder cortarse por semana, quincena, mes o un rango
 * libre: los tres periodos que el equipo entrega a los clientes.
 */

it('resuelve el rango de cada periodo', function () {
    $reports = app(ReportService::class);

    // Semana ISO 32 de 2026: lunes 3 a domingo 9 de agosto.
    [$start, $end] = $reports->resolveRange('semanal', ['year' => 2026, 'week' => 32]);
    expect($start->toDateString())->toBe('2026-08-03')
        ->and($end->toDateString())->toBe('2026-08-09')
        ->and($start->dayOfWeek)->toBe(Carbon\Carbon::MONDAY);

    [$start, $end] = $reports->resolveRange('quincenal', ['year' => 2026, 'month' => 2, 'fortnight' => 2]);
    expect($start->toDateString())->toBe('2026-02-16')
        ->and($end->toDateString())->toBe('2026-02-28');

    [$start, $end] = $reports->resolveRange('mensual', ['year' => 2026, 'month' => 4]);
    expect($start->toDateString())->toBe('2026-04-01')
        ->and($end->toDateString())->toBe('2026-04-30');

    [$start, $end] = $reports->resolveRange('libre', ['from' => '2026-07-10', 'until' => '2026-07-12']);
    expect($start->toDateString())->toBe('2026-07-10')
        ->and($end->toDateString())->toBe('2026-07-12');

    // Un periodo desconocido cae en quincenal, que es el comportamiento previo.
    [$start, $end] = $reports->resolveRange('lo-que-sea', ['year' => 2026, 'month' => 1, 'fortnight' => 1]);
    expect($start->toDateString())->toBe('2026-01-01')
        ->and($end->toDateString())->toBe('2026-01-15');
});

it('ordena el rango libre si las fechas vienen invertidas', function () {
    [$start, $end] = app(ReportService::class)->resolveRange('libre', [
        'from' => '2026-07-20',
        'until' => '2026-07-05',
    ]);

    expect($start->toDateString())->toBe('2026-07-05')
        ->and($end->toDateString())->toBe('2026-07-20');
});

it('cubre febrero bisiesto y la última semana del año', function () {
    $reports = app(ReportService::class);

    [, $end] = $reports->resolveRange('quincenal', ['year' => 2028, 'month' => 2, 'fortnight' => 2]);
    expect($end->toDateString())->toBe('2028-02-29');

    [, $end] = $reports->resolveRange('mensual', ['year' => 2028, 'month' => 2]);
    expect($end->toDateString())->toBe('2028-02-29');

    // 2026 tiene 53 semanas ISO: la 53 empieza el 28 de diciembre.
    [$start, $end] = $reports->resolveRange('semanal', ['year' => 2026, 'week' => 53]);
    expect($start->toDateString())->toBe('2026-12-28')
        ->and($end->toDateString())->toBe('2027-01-03');
});

it('filtra las guías por el periodo elegido', function () {
    seedSeries();

    $user = User::factory()->create();
    $client = Client::factory()->create();
    $product = Product::factory()->create(['stock' => 1000]);
    $guides = app(DispatchGuideService::class);

    // Una guía el 5 de julio (1.ª quincena) y otra el 20 (2.ª quincena).
    foreach (['2026-07-05', '2026-07-20'] as $fecha) {
        $guide = draftGuide($client, [$product->id => 10], $user);
        $guides->issue($guide->fresh('items'), $user);
        $guide->fresh()->update(['issue_date' => $fecha]);
    }

    $reports = app(ReportService::class);

    [$start, $end] = $reports->resolveRange('quincenal', ['year' => 2026, 'month' => 7, 'fortnight' => 1]);
    expect($reports->guidesInRange(null, $start, $end, false))->toHaveCount(1);

    [$start, $end] = $reports->resolveRange('mensual', ['year' => 2026, 'month' => 7]);
    expect($reports->guidesInRange(null, $start, $end, false))->toHaveCount(2);

    [$start, $end] = $reports->resolveRange('semanal', ['year' => 2026, 'week' => 28]); // 6 al 12 de julio
    expect($reports->guidesInRange(null, $start, $end, false))->toHaveCount(0);

    [$start, $end] = $reports->resolveRange('libre', ['from' => '2026-07-19', 'until' => '2026-07-21']);
    expect($reports->guidesInRange(null, $start, $end, false))->toHaveCount(1);
});

it('exporta a Excel en los cuatro periodos y mantiene los enlaces antiguos', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    seedSeries();

    $user = User::factory()->create();
    $user->assignRole('usuario');
    $this->actingAs($user);

    // Enlace antiguo, sin periodo: sigue respondiendo como quincenal.
    $this->get(route('reportes.guias.excel', ['year' => 2026, 'month' => 7, 'fortnight' => 1]))->assertOk();

    $this->get(route('reportes.guias.excel', ['periodType' => 'semanal', 'year' => 2026, 'week' => 32]))->assertOk();
    $this->get(route('reportes.guias.excel', ['periodType' => 'mensual', 'year' => 2026, 'month' => 7]))->assertOk();
    $this->get(route('reportes.guias.excel', ['periodType' => 'libre', 'from' => '2026-07-01', 'until' => '2026-07-31']))->assertOk();

    // Y valida lo que falta en cada modo.
    $this->get(route('reportes.guias.excel', ['periodType' => 'semanal', 'year' => 2026]))
        ->assertSessionHasErrors('week');
    $this->get(route('reportes.guias.excel', ['periodType' => 'libre']))
        ->assertSessionHasErrors(['from', 'until']);

    // La URL vieja del reporte redirige a la nueva.
    $this->get('/reportes/guias-quincenal')->assertRedirect('/reportes/guias');
});

it('los totales dejan fuera las anuladas y las rechazadas por SUNAT', function () {
    $reports = app(ReportService::class);
    $client = Client::factory()->create();

    $hacerFactura = function (App\Enums\InvoiceStatus $estado, float $total) use ($client) {
        return App\Models\Invoice::query()->create([
            'client_id' => $client->id,
            'status' => $estado,
            'issue_date' => '2026-07-10',
            'due_date' => '2026-08-09',
            'payment_type' => 'credito',
            'taxable_amount' => round($total / 1.18, 2),
            'igv' => round($total - $total / 1.18, 2),
            'total' => $total,
            'created_by' => User::factory()->create()->id,
        ]);
    };

    $hacerFactura(App\Enums\InvoiceStatus::Accepted, 1000);
    $hacerFactura(App\Enums\InvoiceStatus::PendingSubmission, 500);
    // Estas dos no son venta válida hoy: una se anuló, la otra la rechazó SUNAT.
    $hacerFactura(App\Enums\InvoiceStatus::Annulled, 9000);
    $hacerFactura(App\Enums\InvoiceStatus::Rejected, 7000);

    [$start, $end] = $reports->resolveRange('mensual', ['year' => 2026, 'month' => 7]);
    $facturas = $reports->invoicesInRange(null, $start, $end, includeAnnulled: true);
    $totales = $reports->invoiceTotals($facturas);

    expect($facturas)->toHaveCount(4)
        ->and($totales['total'])->toBe(1500.0)
        ->and($totales['excluidas'])->toBe(2);
});

it('no le muestra saldos de cobranza a quien no lleva cobranzas', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $usuario = User::factory()->create();
    $usuario->assignRole('usuario');

    $this->actingAs($usuario)->get(route('reportes.facturas'))
        ->assertOk()
        ->assertDontSee('Saldo por cobrar')
        ->assertSee('Total facturado');

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)->get(route('reportes.facturas'))
        ->assertOk()
        ->assertSee('Saldo por cobrar');
});
