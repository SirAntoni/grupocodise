<?php

namespace App\Livewire\Clients;

use App\Models\Client;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $business_name = '';

    public string $ruc = '';

    public string $address = '';

    public ?string $district = null;

    public ?string $phone = null;

    public ?string $email = null;

    public ?string $contact_name = null;

    protected function rules(): array
    {
        return [
            'business_name' => ['required', 'string', 'max:255'],
            'ruc' => ['required', 'digits:11', Rule::unique('clients', 'ruc')->ignore($this->editingId)],
            'address' => ['required', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected array $validationAttributes = [
        'business_name' => 'razón social',
        'ruc' => 'RUC',
        'address' => 'dirección',
        'district' => 'distrito',
        'phone' => 'teléfono',
        'email' => 'correo',
        'contact_name' => 'contacto',
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->authorize('clients.manage');
        $this->reset(['editingId', 'business_name', 'ruc', 'address', 'district', 'phone', 'email', 'contact_name']);
        $this->resetValidation();
        $this->showForm = true;
    }

    public function openEdit(int $clientId): void
    {
        $this->authorize('clients.manage');
        $client = Client::query()->findOrFail($clientId);

        $this->editingId = $client->id;
        $this->fill($client->only([
            'business_name', 'ruc', 'address', 'district', 'phone', 'email', 'contact_name',
        ]));
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorize('clients.manage');
        $data = $this->validate();

        if ($this->editingId) {
            Client::query()->findOrFail($this->editingId)->update($data);
            session()->now('ok', 'Cliente actualizado.');
        } else {
            Client::query()->create($data);
            session()->now('ok', 'Cliente registrado.');
        }

        $this->showForm = false;
    }

    public function toggleActive(int $clientId): void
    {
        $this->authorize('clients.manage');
        $client = Client::query()->findOrFail($clientId);
        $client->update(['is_active' => ! $client->is_active]);
        session()->now('ok', $client->is_active ? 'Cliente activado.' : 'Cliente desactivado.');
    }

    public function render(): View
    {
        $clients = Client::query()
            ->when($this->search !== '', fn ($q) => $q->where(fn ($s) => $s
                ->where('business_name', 'like', "%{$this->search}%")
                ->orWhere('ruc', 'like', "%{$this->search}%")))
            ->orderBy('business_name')
            ->paginate(15);

        return view('livewire.clients.index', ['clients' => $clients]);
    }
}
