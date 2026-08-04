<?php

namespace App\Http\Controllers\Pdf;

use App\Http\Controllers\Controller;
use App\Models\CreditNote;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class CreditNotePdfController extends Controller
{
    public function __invoke(Request $request, CreditNote $creditNote)
    {
        $creditNote->load(['invoice.client', 'invoice.items.product', 'electronicDocument']);

        $pdf = Pdf::loadView('pdf.nota-credito', [
            'creditNote' => $creditNote,
            'company' => config('facturacion.company'),
        ])->setPaper('a4');

        $nombre = 'nota-credito-'.$creditNote->full_number.'.pdf';

        return $request->boolean('descargar') ? $pdf->download($nombre) : $pdf->stream($nombre);
    }
}
