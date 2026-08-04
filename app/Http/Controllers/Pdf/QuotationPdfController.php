<?php

namespace App\Http\Controllers\Pdf;

use App\Http\Controllers\Controller;
use App\Models\Quotation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class QuotationPdfController extends Controller
{
    public function __invoke(Request $request, Quotation $quotation)
    {
        $quotation->load(['client', 'items.product', 'seller', 'createdBy']);

        $pdf = Pdf::loadView('pdf.cotizacion', [
            'quotation' => $quotation,
            'company' => config('facturacion.company'),
        ])->setPaper('a4');

        $nombre = 'cotizacion-'.$quotation->code.'.pdf';

        return $request->boolean('descargar') ? $pdf->download($nombre) : $pdf->stream($nombre);
    }
}
