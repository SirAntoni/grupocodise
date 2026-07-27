<?php

namespace App\Livewire\Requirements;

use App\Enums\RequirementStatus;
use App\Models\Client;
use App\Models\Requirement;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public ?int $clientFilter = null;

    public string $statusFilter = '';

    public ?string $from = null;

    public ?string $until = null;

    public function updating($property): void
    {
        if (in_array($property, ['clientFilter', 'statusFilter', 'from', 'until'], true)) {
            $this->resetPage();
        }
    }

    public function annul(int $requirementId): void
    {
        $requirement = Requirement::query()->findOrFail($requirementId);
        $this->authorize('annul', $requirement);

        $requirement->update(['status' => RequirementStatus::Annulled]);
        session()->now('ok', "Requerimiento {$requirement->code} anulado.");
    }

    public function createGuide(int $requirementId, \App\Services\DispatchGuideService $service): void
    {
        $this->authorize('create', \App\Models\DispatchGuide::class);

        $requirement = Requirement::query()->with('items')->findOrFail($requirementId);

        try {
            $guide = $service->createFromRequirement($requirement, auth()->user());
        } catch (\InvalidArgumentException $e) {
            session()->now('error', $e->getMessage());

            return;
        }

        session()->flash('ok', 'Borrador de guía creado a partir del requerimiento.');
        $this->redirectRoute('guias.editar', ['dispatchGuide' => $guide->id], navigate: true);
    }

    public function render(): View
    {
        $requirements = Requirement::query()
            ->with(['client', 'items'])
            ->withCount('dispatchGuides')
            ->filter($this->clientFilter, $this->statusFilter ?: null, $this->from, $this->until)
            ->latest('id')
            ->paginate(15);

        return view('livewire.requirements.index', [
            'requirements' => $requirements,
            'clients' => Client::query()->orderBy('business_name')->get(['id', 'business_name']),
            'statuses' => RequirementStatus::cases(),
        ]);
    }
}
