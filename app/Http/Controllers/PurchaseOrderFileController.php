<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\Storage;

class PurchaseOrderFileController extends Controller
{
    public function __invoke(PurchaseOrder $purchaseOrder)
    {
        abort_if($purchaseOrder->pdf_path === null, 404, 'La orden de compra no tiene PDF adjunto.');
        abort_unless(Storage::exists($purchaseOrder->pdf_path), 404, 'El archivo adjunto no se encuentra.');

        return Storage::download(
            $purchaseOrder->pdf_path,
            'oc-'.str_replace(['/', '\\'], '-', $purchaseOrder->number).'.pdf',
        );
    }
}
