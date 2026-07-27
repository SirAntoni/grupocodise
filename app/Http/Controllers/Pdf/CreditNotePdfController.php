<?php

namespace App\Http\Controllers\Pdf;

use App\Http\Controllers\Controller;
use App\Models\CreditNote;
use Barryvdh\DomPDF\Facade\Pdf;

class CreditNotePdfController extends Controller
{
    public function __invoke(CreditNote $creditNote)
    {
        $creditNote->load(['invoice.client', 'invoice.items.product', 'electronicDocument']);

        $pdf = Pdf::loadView('pdf.nota-credito', [
            'creditNote' => $creditNote,
            'company' => config('facturacion.company'),
        ])->setPaper('a4');

        return $pdf->stream('nota-credito-'.$creditNote->full_number.'.pdf');
    }
}
