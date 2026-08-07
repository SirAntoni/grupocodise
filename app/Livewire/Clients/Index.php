<?php

namespace App\Livewire\Clients;

use App\Models\Client;
use App\Support\SunatCatalogs;
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

    public string $document_type = '6';

    public string $document_number = '';

    public string $business_name = '';

    public string $address = '';

    public ?string $district = null;

    public ?string $phone = null;

    public ?string $email = null;

    public ?string $contact_name = null;

    public ?string $ubigeo = null;

    /** Estado/condición del contribuyente según el padrón (informativo). */
    public ?array $rucInfo = null;

    protected function rules(): array
    {
        return [
            'document_type' => ['required', Rule::in(array_keys(SunatCatalogs::DOCUMENT_TYPES))],
            'document_number' => [
                'required',
                SunatCatalogs::documentNumberRule($this->document_type),
                Rule::unique('clients', 'document_number')
                    ->where('document_type', $this->document_type)
                    ->ignore($this->editingId),
            ],
            'business_name' => ['required', 'string', 'max:255'],
            // Una persona natural puede no tener dirección declarada.
            'address' => [$this->document_type === '6' ? 'required' : 'nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:100'],
            'ubigeo' => ['nullable', 'digits:6'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected array $validationAttributes = [
        'document_type' => 'tipo de documento',
        'document_number' => 'número de documento',
        'business_name' => 'nombre o razón social',
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

    /** Cambiar de tipo de documento invalida lo consultado al padrón. */
    public function updatedDocumentType(): void
    {
        $this->rucInfo = null;
        $this->resetValidation('document_number');
    }

    public function openCreate(): void
    {
        $this->authorize('clients.manage');
        $this->reset([
            'editingId', 'document_type', 'document_number', 'business_name',
            'address', 'district', 'ubigeo', 'phone', 'email', 'contact_name', 'rucInfo',
        ]);
        $this->resetValidation();
        $this->showForm = true;
    }

    public function openEdit(int $clientId): void
    {
        $this->authorize('clients.manage');
        $client = Client::query()->findOrFail($clientId);

        $this->editingId = $client->id;
        $this->rucInfo = null;
        $this->fill($client->only([
            'document_type', 'document_number', 'business_name',
            'address', 'district', 'ubigeo', 'phone', 'email', 'contact_name',
        ]));
        $this->resetValidation();
        $this->showForm = true;
    }

    /**
     * Autocompleta los datos desde el padrón (API Migo): por RUC trae la razón
     * social y el domicilio fiscal; por DNI, los nombres.
     */
    public function lookupDocument(\App\Services\MigoService $migo): void
    {
        $this->authorize('clients.manage');
        $this->resetValidation('document_number');
        $this->rucInfo = null;

        $largo = $this->document_type === '6' ? 11 : 8;

        if (! in_array($this->document_type, ['6', '1'], true)) {
            $this->addError('document_number', 'La búsqueda automática solo está disponible para RUC y DNI.');

            return;
        }

        if (! preg_match('/^\d{'.$largo.'}$/', (string) $this->document_number)) {
            $this->addError('document_number', "Digita los {$largo} dígitos antes de buscar.");

            return;
        }

        // Si ya es cliente, no hay nada que consultar (ni crédito que gastar).
        $existing = Client::query()
            ->where('document_type', $this->document_type)
            ->where('document_number', $this->document_number)
            ->when($this->editingId, fn ($q) => $q->whereKeyNot($this->editingId))
            ->first();

        if ($existing) {
            $this->addError('document_number', "Este documento ya está registrado como cliente: {$existing->business_name}.");

            return;
        }

        if (! $migo->isConfigured()) {
            $this->addError('document_number', 'La búsqueda automática no está disponible; digita los datos manualmente.');

            return;
        }

        if ($this->document_type === '1') {
            $persona = $migo->lookupDni($this->document_number);

            if ($persona === null) {
                $this->addError('document_number', 'No se encontró el DNI en el padrón; digita los datos manualmente.');

                return;
            }

            $this->business_name = trim($persona['nombres'].' '.$persona['apellidos']);

            return;
        }

        $info = $migo->lookupRuc($this->document_number);

        if ($info === null) {
            $this->addError('document_number', 'No se encontró el RUC en el padrón (o el servicio no respondió); digita los datos manualmente.');

            return;
        }

        $this->business_name = $info['razon_social'];
        $this->address = $info['direccion'] ?? $this->address;
        $this->district = $info['distrito'] ?? $this->district;
        $this->ubigeo = $info['ubigeo'] ?? $this->ubigeo;
        $this->rucInfo = $info;
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
                ->orWhere('document_number', 'like', "%{$this->search}%")))
            ->orderBy('business_name')
            ->paginate(15);

        return view('livewire.clients.index', [
            'clients' => $clients,
            'documentTypes' => SunatCatalogs::DOCUMENT_TYPES,
        ]);
    }
}
