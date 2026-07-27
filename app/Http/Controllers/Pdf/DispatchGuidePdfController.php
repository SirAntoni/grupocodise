<?php

namespace App\Http\Controllers\Pdf;

use App\Http\Controllers\Controller;
use App\Models\DispatchGuide;
use Barryvdh\DomPDF\Facade\Pdf;

class DispatchGuidePdfController extends Controller
{
    public function __invoke(DispatchGuide $dispatchGuide, ?string $copy = null)
    {
        abort_if($dispatchGuide->isDraft(), 404, 'La guía aún está en borrador.');

        $dispatchGuide->load(['client', 'items.product', 'electronicDocument', 'requirement']);

        $copies = match ($copy) {
            'cliente' => ['COPIA CLIENTE'],
            'empresa' => ['COPIA EMPRESA'],
            default => ['COPIA CLIENTE', 'COPIA EMPRESA'],
        };

        $pdf = Pdf::loadView('pdf.guia', [
            'guide' => $dispatchGuide,
            'copies' => $copies,
            'company' => config('facturacion.company'),
        ])->setPaper('a4');

        return $pdf->stream('guia-'.($dispatchGuide->full_number ?? $dispatchGuide->id).'.pdf');
    }
}
