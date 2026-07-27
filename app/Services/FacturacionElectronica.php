<?php

namespace App\Services;

use App\Models\CreditNote;
use App\Models\DispatchGuide;
use App\Models\Invoice;
use App\Services\Facturacion\SunatResult;

/**
 * Puerto de emisión electrónica ante SUNAT. La implementación concreta
 * (Greenter) queda aislada detrás de esta interfaz.
 */
interface FacturacionElectronica
{
    /** Envía una factura por el SEE (SOAP). */
    public function sendInvoice(Invoice $invoice): SunatResult;

    /** Envía una nota de crédito por el SEE (SOAP). */
    public function sendCreditNote(CreditNote $creditNote): SunatResult;

    /** Envía una guía de remisión por el API REST de GRE; puede devolver ticket. */
    public function sendDispatchGuide(DispatchGuide $guide): SunatResult;

    /** Consulta el estado de una GRE enviada previamente (ticket). */
    public function checkDispatchGuideStatus(DispatchGuide $guide, string $ticket): SunatResult;
}
