<?php

namespace App\Http\Controllers\Pdf;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class InvoicePdfController extends Controller
{
    public function __invoke(Request $request, Invoice $invoice)
    {
        abort_if($invoice->full_number === null, 404, 'La factura aún está en borrador.');

        $invoice->load(['client', 'items.product', 'dispatchGuides', 'electronicDocument', 'purchaseOrder']);

        $pdf = Pdf::loadView('pdf.factura', [
            'invoice' => $invoice,
            'company' => config('facturacion.company'),
        ])->setPaper('a4');

        $nombre = 'factura-'.$invoice->full_number.'.pdf';

        return $request->boolean('descargar') ? $pdf->download($nombre) : $pdf->stream($nombre);
    }
}
