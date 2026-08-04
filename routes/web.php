<?php

use App\Http\Controllers\Pdf\CreditNotePdfController;
use App\Http\Controllers\Pdf\DispatchGuidePdfController;
use App\Http\Controllers\Pdf\InvoicePdfController;
use App\Http\Controllers\PurchaseOrderFileController;
use App\Http\Controllers\Reports\GuidesExportController;
use App\Http\Controllers\Reports\DifferencesExportController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/panel');

Route::middleware(['auth', 'verified', 'active'])->group(function () {

    Route::view('panel', 'dashboard')->name('dashboard');

    // Clientes
    Route::middleware('permission:clients.view')->group(function () {
        Route::get('clientes', App\Livewire\Clients\Index::class)->name('clientes.index');
    });

    // Productos y stock (operaciones)
    Route::middleware('permission:products.view')->group(function () {
        Route::get('productos', App\Livewire\Products\Index::class)->name('productos.index');
    });
    Route::middleware('permission:stock.view')->group(function () {
        Route::get('kardex', App\Livewire\Stock\Kardex::class)->name('kardex.index');
    });

    // Requerimientos (operaciones)
    Route::middleware('permission:requirements.view')->group(function () {
        Route::get('requerimientos', App\Livewire\Requirements\Index::class)->name('requerimientos.index');
        Route::get('requerimientos/{requirement}', App\Livewire\Requirements\Show::class)
            ->whereNumber('requirement')->name('requerimientos.ver');
    });
    Route::middleware('permission:requirements.manage')->group(function () {
        Route::get('requerimientos/crear', App\Livewire\Requirements\Form::class)->name('requerimientos.crear');
        Route::get('requerimientos/{requirement}/editar', App\Livewire\Requirements\Form::class)->name('requerimientos.editar');
    });

    // Guías de despacho (operaciones)
    Route::middleware('permission:guides.view')->group(function () {
        Route::get('guias', App\Livewire\Guides\Index::class)->name('guias.index');
        Route::get('guias/{dispatchGuide}', App\Livewire\Guides\Show::class)
            ->whereNumber('dispatchGuide')->name('guias.ver');
        Route::get('guias/{dispatchGuide}/pdf/{copy?}', DispatchGuidePdfController::class)->name('guias.pdf');
    });
    Route::middleware('permission:guides.manage')->group(function () {
        // La creación de guías es una acción Livewire desde el requerimiento
        // (no una ruta GET: un GET no debe escribir en la base).
        Route::get('guias/{dispatchGuide}/editar', App\Livewire\Guides\Form::class)->name('guias.editar');
    });

    // Facturación (operaciones)
    Route::middleware('permission:invoices.view')->group(function () {
        Route::get('facturas', App\Livewire\Invoices\Index::class)->name('facturas.index');
        Route::get('facturas/{invoice}', App\Livewire\Invoices\Show::class)
            ->whereNumber('invoice')->name('facturas.ver');
        Route::get('facturas/{invoice}/pdf', InvoicePdfController::class)->name('facturas.pdf');
        Route::get('notas-credito/{creditNote}/pdf', CreditNotePdfController::class)->name('notas-credito.pdf');
    });
    Route::middleware('permission:invoices.manage')->group(function () {
        Route::get('facturas/crear', App\Livewire\Invoices\Form::class)->name('facturas.crear');
        Route::get('facturas/{invoice}/editar', App\Livewire\Invoices\Form::class)->name('facturas.editar');
    });

    // Cotizaciones (operaciones)
    Route::middleware('permission:quotations.view')->group(function () {
        Route::get('cotizaciones', App\Livewire\Quotations\Index::class)->name('cotizaciones.index');
        Route::get('cotizaciones/{quotation}', App\Livewire\Quotations\Show::class)
            ->whereNumber('quotation')->name('cotizaciones.ver');
    });
    Route::middleware('permission:quotations.manage')->group(function () {
        Route::get('cotizaciones/crear', App\Livewire\Quotations\Form::class)->name('cotizaciones.crear');
        Route::get('cotizaciones/{quotation}/editar', App\Livewire\Quotations\Form::class)->name('cotizaciones.editar');
    });

    // Órdenes de compra (operaciones)
    Route::middleware('permission:purchase-orders.view')->group(function () {
        Route::get('ordenes-compra', App\Livewire\PurchaseOrders\Index::class)->name('ordenes-compra.index');
        Route::get('ordenes-compra/{purchaseOrder}/pdf', PurchaseOrderFileController::class)->name('ordenes-compra.pdf');
    });

    // Cuentas por cobrar (operaciones)
    Route::middleware('permission:receivables.view')->group(function () {
        Route::get('cobranzas', App\Livewire\Receivables\Index::class)->name('cobranzas.index');
        Route::get('pagos', App\Livewire\Payments\Index::class)->name('pagos.index');
    });

    // Reportes
    Route::middleware('permission:reports.view')->group(function () {
        Route::redirect('reportes/guias-quincenal', 'reportes/guias');
        Route::get('reportes/guias', App\Livewire\Reports\GuidesByPeriod::class)->name('reportes.guias');
        Route::get('reportes/guias/excel', GuidesExportController::class)->name('reportes.guias.excel');
        Route::get('reportes/diferencias', App\Livewire\Reports\Differences::class)->name('reportes.diferencias');
        Route::get('reportes/diferencias/excel', DifferencesExportController::class)->name('reportes.diferencias.excel');
    });

    // Administración
    Route::middleware('permission:users.manage')->group(function () {
        Route::get('usuarios', App\Livewire\Users\Index::class)->name('usuarios.index');
    });
    Route::middleware('permission:series.manage')->group(function () {
        Route::get('series', App\Livewire\SeriesAdmin\Index::class)->name('series.index');
    });

    Route::view('manual', 'manual')->name('manual');
    Route::view('perfil', 'profile')->name('profile');
});

require __DIR__.'/auth.php';
