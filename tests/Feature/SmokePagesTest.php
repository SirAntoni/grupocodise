<?php

use App\Jobs\SubmitElectronicDocument;
use App\Models\Client;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Requirement;
use App\Models\User;
use App\Services\CreditNoteService;
use App\Services\DispatchGuideService;
use App\Services\InvoiceService;
use App\Services\QuotationService;
use App\Services\ReceivableService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Storage;

/**
 * Red de seguridad del frontend: toda página GET del sistema debe responder
 * 200 para un admin con datos reales de cada módulo.
 */
it('todas las páginas del sistema responden correctamente', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    seedSeries();

    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->actingAs($admin);

    $client = Client::factory()->create();
    $product = Product::factory()->create(['stock' => 500]);

    // Requerimiento pendiente (editable) y otro despachado con guía emitida.
    $pending = Requirement::query()->create([
        'code' => 'REQ-90001',
        'client_id' => $client->id,
        'crew_chief' => 'Prueba',
        'district' => 'Lima',
        'required_date' => now()->addDay()->toDateString(),
        'created_by' => $admin->id,
    ]);
    $pending->items()->create(['product_id' => $product->id, 'quantity' => 3]);

    $guideService = app(DispatchGuideService::class);
    $draftGuide = draftGuide($client, [$product->id => 5], $admin);
    $issuedGuide = $guideService->issue(draftGuide($client, [$product->id => 10], $admin), $admin);

    // Factura aceptada (driver fake) con CxC y un pago; luego NC aceptada.
    $invoiceService = app(InvoiceService::class);
    $accepted = $invoiceService->createDraftFromGuideNumbers([$issuedGuide->full_number], $admin);
    $accepted = $invoiceService->applyPricing($accepted, [$accepted->items->first()->id => 10.00], null);
    $accepted = $invoiceService->issue($accepted, $admin);
    SubmitElectronicDocument::dispatchSync($accepted->electronicDocument()->first());
    $accepted->refresh();

    app(ReceivableService::class)->registerPayment($accepted->receivable, 50, now()->toDateString(), 'transferencia', 'OP-1', null, $admin);

    $creditNote = app(CreditNoteService::class)->annulInvoice($accepted, 'Prueba de smoke', $admin);
    SubmitElectronicDocument::dispatchSync($creditNote->electronicDocument()->first());

    // Borrador de factura vigente (la guía quedó libre tras la NC).
    $draftInvoice = $invoiceService->createDraftFromGuideNumbers([$issuedGuide->full_number], $admin);

    $quotation = app(QuotationService::class)->save(null, [
        'client_id' => $client->id,
        'issue_date' => now()->toDateString(),
        'valid_until' => now()->addDays(15)->toDateString(),
        'notes' => null,
    ], [
        ['product_id' => $product->id, 'description' => $product->name, 'unit_code' => 'NIU', 'quantity' => 2, 'unit_value' => 10],
    ], $admin);

    Storage::put('ordenes-compra/smoke.pdf', '%PDF-1.4 smoke');
    $po = PurchaseOrder::query()->create([
        'client_id' => $client->id,
        'origin' => 'recibida',
        'number' => 'OC-SMOKE-1',
        'date' => now()->toDateString(),
        'amount' => 100,
        'pdf_path' => 'ordenes-compra/smoke.pdf',
        'created_by' => $admin->id,
    ]);

    $routes = [
        'panel' => route('dashboard'),
        'manual' => route('manual'),
        'perfil' => route('profile'),
        'clientes' => route('clientes.index'),
        'productos' => route('productos.index'),
        'kardex' => route('kardex.index'),
        'requerimientos' => route('requerimientos.index'),
        'requerimiento ver' => route('requerimientos.ver', $pending),
        'requerimiento crear' => route('requerimientos.crear'),
        'requerimiento editar' => route('requerimientos.editar', $pending),
        'guias' => route('guias.index'),
        'guia ver (borrador)' => route('guias.ver', $draftGuide),
        'guia ver (emitida)' => route('guias.ver', $issuedGuide),
        'guia editar' => route('guias.editar', $draftGuide),
        'guia pdf' => route('guias.pdf', $issuedGuide),
        'facturas' => route('facturas.index'),
        'factura crear' => route('facturas.crear'),
        'factura ver (anulada)' => route('facturas.ver', $accepted),
        'factura ver (borrador)' => route('facturas.ver', $draftInvoice),
        'factura editar' => route('facturas.editar', $draftInvoice),
        'factura pdf' => route('facturas.pdf', $accepted),
        'nota de crédito pdf' => route('notas-credito.pdf', $creditNote),
        'cotizaciones' => route('cotizaciones.index'),
        'cotización crear' => route('cotizaciones.crear'),
        'cotización ver' => route('cotizaciones.ver', $quotation),
        'cotización editar' => route('cotizaciones.editar', $quotation),
        'órdenes de compra' => route('ordenes-compra.index'),
        'oc pdf adjunto' => route('ordenes-compra.pdf', $po),
        'cobranzas' => route('cobranzas.index'),
        'pagos' => route('pagos.index'),
        'reporte de guías' => route('reportes.guias'),
        'reporte de guías semanal' => route('reportes.guias', ['periodType' => 'semanal']),
        'reporte de guías mensual' => route('reportes.guias', ['periodType' => 'mensual']),
        'reporte de guías rango libre' => route('reportes.guias', ['periodType' => 'libre']),
        'reporte de guías excel' => route('reportes.guias.excel', ['year' => now()->year, 'month' => now()->month, 'fortnight' => 1]),
        'reporte diferencias' => route('reportes.diferencias'),
        'reporte diferencias excel' => route('reportes.diferencias.excel', ['from' => now()->startOfMonth()->toDateString(), 'until' => now()->toDateString()]),
        'usuarios' => route('usuarios.index'),
        'series' => route('series.index'),
    ];

    foreach ($routes as $name => $url) {
        $status = $this->get($url)->baseResponse->getStatusCode();

        expect($status)->toBe(200, "La página «{$name}» ({$url}) devolvió {$status}.");
    }
});
