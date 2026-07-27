<?php

namespace App\Services\Facturacion;

use App\Models\CreditNote;
use App\Models\DispatchGuide;
use App\Models\Invoice;
use App\Services\FacturacionElectronica;

/**
 * Implementación de prueba: acepta todo sin llamar a SUNAT.
 * Útil en tests y en desarrollo sin conectividad (FACT_DRIVER=fake).
 */
class FakeFacturacionElectronica implements FacturacionElectronica
{
    /** @var array<string, SunatResult> respuestas forzadas por full_number */
    public array $forcedResults = [];

    public function forceResult(string $fullNumber, SunatResult $result): void
    {
        $this->forcedResults[$fullNumber] = $result;
    }

    public function sendInvoice(Invoice $invoice): SunatResult
    {
        return $this->forcedResults[$invoice->full_number]
            ?? SunatResult::accepted('0', 'La Factura ha sido aceptada', $this->fakeXml($invoice->full_number), 'CDR-FAKE', 'hash-fake');
    }

    public function sendCreditNote(CreditNote $creditNote): SunatResult
    {
        return $this->forcedResults[$creditNote->full_number]
            ?? SunatResult::accepted('0', 'La Nota de Crédito ha sido aceptada', $this->fakeXml($creditNote->full_number), 'CDR-FAKE', 'hash-fake');
    }

    public function sendDispatchGuide(DispatchGuide $guide): SunatResult
    {
        return $this->forcedResults[$guide->full_number]
            ?? SunatResult::accepted('0', 'La Guía ha sido aceptada', $this->fakeXml($guide->full_number), 'CDR-FAKE', 'hash-fake');
    }

    public function checkDispatchGuideStatus(DispatchGuide $guide, string $ticket): SunatResult
    {
        return SunatResult::accepted('0', 'La Guía ha sido aceptada', null, 'CDR-FAKE', null);
    }

    protected function fakeXml(string $fullNumber): string
    {
        return '<?xml version="1.0"?><Fake documento="'.$fullNumber.'"/>';
    }
}
