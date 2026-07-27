<?php

namespace App\Livewire\Guides;

use App\Models\DispatchGuide;
use App\Services\DispatchGuideService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Show extends Component
{
    public DispatchGuide $guide;

    public bool $showAnnulForm = false;

    public string $annulment_reason = '';

    public function mount(int $dispatchGuide): void
    {
        $this->guide = DispatchGuide::query()
            ->with(['client', 'requirement', 'items.product', 'series', 'electronicDocument', 'createdBy', 'issuedBy', 'annulledBy', 'invoices'])
            ->findOrFail($dispatchGuide);

        $this->authorize('view', $this->guide);
    }

    public function issue(DispatchGuideService $service): void
    {
        $this->authorize('issue', $this->guide);

        try {
            $service->issue($this->guide, auth()->user());
            session()->now('ok', "Guía {$this->guide->full_number} emitida y en cola de envío a SUNAT.");
        } catch (\Throwable $e) {
            session()->now('error', $e->getMessage());
        }

        $this->guide->refresh()->load('items.product', 'electronicDocument');
    }

    public function resend(DispatchGuideService $service): void
    {
        $this->authorize('resend', $this->guide);

        try {
            $service->resend($this->guide, auth()->user());
            session()->now('ok', 'Guía reencolada para envío/consulta ante SUNAT.');
        } catch (\InvalidArgumentException $e) {
            session()->now('error', $e->getMessage());
        }

        $this->guide->refresh()->load('items.product', 'electronicDocument');
    }

    public function duplicate(DispatchGuideService $service): void
    {
        $this->authorize('duplicate', $this->guide);

        $copy = $service->duplicate($this->guide, auth()->user());
        session()->flash('ok', 'Guía duplicada como borrador; revísala y emítela cuando esté lista.');
        $this->redirectRoute('guias.editar', ['dispatchGuide' => $copy->id], navigate: true);
    }

    public function annul(DispatchGuideService $service): void
    {
        $this->authorize('annul', $this->guide);
        $this->validate(
            ['annulment_reason' => ['required', 'string', 'min:5', 'max:255']],
            [],
            ['annulment_reason' => 'motivo de anulación'],
        );

        try {
            $service->annul($this->guide, $this->annulment_reason, auth()->user());
            session()->now('ok', 'Guía anulada y stock restituido.');
            $this->showAnnulForm = false;
        } catch (\InvalidArgumentException $e) {
            session()->now('error', $e->getMessage());
        }

        $this->guide->refresh()->load('items.product', 'electronicDocument');
    }

    public function deleteDraft(): void
    {
        $this->authorize('delete', $this->guide);

        $this->guide->items()->delete();
        $this->guide->delete();

        session()->flash('ok', 'Borrador de guía eliminado.');
        $this->redirectRoute('guias.index', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.guides.show');
    }
}
