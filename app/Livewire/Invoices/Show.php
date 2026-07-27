<?php

namespace App\Livewire\Invoices;

use App\Jobs\SubmitElectronicDocument;
use App\Models\Invoice;
use App\Services\CreditNoteService;
use App\Services\InvoiceService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Show extends Component
{
    public Invoice $invoice;

    public bool $showAnnulForm = false;

    public string $annulment_reason = '';

    public function mount(Invoice $invoice): void
    {
        $this->invoice = $invoice->load([
            'client', 'series', 'items.product', 'dispatchGuides.series',
            'electronicDocument.logs', 'creditNotes.electronicDocument',
            'receivable.payments', 'purchaseOrder', 'createdBy', 'issuedBy',
        ]);

        $this->authorize('view', $this->invoice);
    }

    public function resend(InvoiceService $service): void
    {
        $this->authorize('resend', $this->invoice);

        try {
            $service->resend($this->invoice, auth()->user());
            session()->now('ok', 'Factura reencolada para envío a SUNAT.');
        } catch (\InvalidArgumentException $e) {
            session()->now('error', $e->getMessage());
        }

        $this->refreshInvoice();
    }

    /** Procesa el envío pendiente de inmediato, sin esperar al worker. */
    public function processNow(): void
    {
        $this->authorize('invoices.manage');

        $document = $this->invoice->electronicDocument;

        if (! $document || $document->sunat_status->isAccepted()) {
            session()->now('error', 'No hay envío pendiente que procesar.');

            return;
        }

        try {
            SubmitElectronicDocument::dispatchSync($document);
            session()->now('ok', 'Envío procesado; revisa el estado SUNAT.');
        } catch (\Throwable $e) {
            report($e);
            session()->now('error', 'El envío falló por un error interno; revisa el estado SUNAT o el log del sistema.');
        }

        $this->refreshInvoice();
    }

    public function annul(CreditNoteService $service): void
    {
        $this->authorize('annul', $this->invoice);
        $this->validate(
            ['annulment_reason' => ['required', 'string', 'min:5', 'max:255']],
            [],
            ['annulment_reason' => 'motivo de anulación'],
        );

        try {
            $creditNote = $service->annulInvoice($this->invoice, $this->annulment_reason, auth()->user());
            session()->now('ok', "Nota de crédito {$creditNote->full_number} emitida y en cola de envío a SUNAT.");
            $this->showAnnulForm = false;
        } catch (\InvalidArgumentException $e) {
            session()->now('error', $e->getMessage());
        }

        $this->refreshInvoice();
    }

    public function deleteDraft(InvoiceService $service): void
    {
        $this->authorize('delete', $this->invoice);

        $service->deleteDraft($this->invoice);

        session()->flash('ok', 'Borrador de factura eliminado; las guías quedaron libres.');
        $this->redirectRoute('facturas.index', navigate: true);
    }

    protected function refreshInvoice(): void
    {
        $this->invoice->refresh()->load([
            'client', 'series', 'items.product', 'dispatchGuides.series',
            'electronicDocument.logs', 'creditNotes.electronicDocument',
            'receivable.payments', 'purchaseOrder', 'createdBy', 'issuedBy',
        ]);
    }

    public function render(): View
    {
        return view('livewire.invoices.show');
    }
}
