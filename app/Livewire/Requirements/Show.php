<?php

namespace App\Livewire\Requirements;

use App\Enums\RequirementStatus;
use App\Models\Requirement;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Show extends Component
{
    public Requirement $requirement;

    public function mount(Requirement $requirement): void
    {
        $this->requirement = $requirement->load(['client', 'items.product', 'dispatchGuides.series', 'createdBy']);

        $this->authorize('view', $this->requirement);
    }

    public function annul(): void
    {
        $this->authorize('annul', $this->requirement);

        $this->requirement->update(['status' => RequirementStatus::Annulled]);
        session()->now('ok', 'Requerimiento anulado.');
        $this->requirement->refresh();
    }

    public function createGuide(\App\Services\DispatchGuideService $service): void
    {
        $this->authorize('create', \App\Models\DispatchGuide::class);

        try {
            $guide = $service->createFromRequirement($this->requirement, auth()->user());
        } catch (\InvalidArgumentException $e) {
            session()->now('error', $e->getMessage());

            return;
        }

        session()->flash('ok', 'Borrador de guía creado a partir del requerimiento.');
        $this->redirectRoute('guias.editar', ['dispatchGuide' => $guide->id], navigate: true);
    }

    public function render(): View
    {
        return view('livewire.requirements.show');
    }
}
