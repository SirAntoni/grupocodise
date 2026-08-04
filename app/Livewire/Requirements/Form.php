<?php

namespace App\Livewire\Requirements;

use App\Models\Client;
use App\Models\Product;
use App\Models\Requirement;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Form extends Component
{
    public ?Requirement $requirement = null;

    public ?int $client_id = null;

    public string $crew_chief = '';

    public string $district = '';

    public ?string $delivery_address = null;

    public ?string $required_date = null;

    public ?string $notes = null;

    /** @var array<int, array{product_id: int|null, quantity: string|null}> */
    public array $items = [];

    public function mount(?Requirement $requirement = null): void
    {
        if ($requirement) {
            $this->requirement = $requirement->load('items');
            $this->authorize('update', $this->requirement);

            $this->client_id = $this->requirement->client_id;
            $this->crew_chief = $this->requirement->crew_chief;
            $this->district = $this->requirement->district;
            $this->delivery_address = $this->requirement->delivery_address;
            $this->required_date = $this->requirement->required_date->format('Y-m-d');
            $this->notes = $this->requirement->notes;
            $this->items = $this->requirement->items
                ->map(fn ($item) => [
                    'key' => (string) \Illuminate\Support\Str::uuid(),
                    'product_id' => $item->product_id,
                    'quantity' => (string) $item->quantity,
                ])
                ->all();
        } else {
            $this->authorize('create', Requirement::class);
            $this->items = [['key' => (string) \Illuminate\Support\Str::uuid(), 'product_id' => null, 'quantity' => null]];
        }
    }

    protected function rules(): array
    {
        return [
            'client_id' => ['required', 'exists:clients,id'],
            'crew_chief' => ['required', 'string', 'max:255'],
            'district' => ['required', 'string', 'max:100'],
            'delivery_address' => ['nullable', 'string', 'max:255'],
            'required_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id', 'distinct'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
        ];
    }

    protected array $validationAttributes = [
        'client_id' => 'cliente',
        'crew_chief' => 'jefe de cuadrilla',
        'district' => 'distrito',
        'delivery_address' => 'dirección de entrega',
        'required_date' => 'fecha requerida',
        'notes' => 'observaciones',
        'items.*.product_id' => 'producto',
        'items.*.quantity' => 'cantidad',
    ];

    public function addItem(): void
    {
        $this->items[] = ['key' => (string) \Illuminate\Support\Str::uuid(), 'product_id' => null, 'quantity' => null];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function save(): void
    {
        $data = $this->validate();

        DB::transaction(function () use ($data) {
            if ($this->requirement) {
                $this->authorize('update', $this->requirement);

                $this->requirement->update([
                    'client_id' => $data['client_id'],
                    'crew_chief' => $data['crew_chief'],
                    'district' => $data['district'],
                    'delivery_address' => $data['delivery_address'],
                    'required_date' => $data['required_date'],
                    'notes' => $data['notes'],
                ]);
                $this->requirement->items()->delete();
            } else {
                $this->authorize('create', Requirement::class);

                // El código se deriva del id autoincremental: dos guardados
                // concurrentes jamás chocan en el unique de `code`.
                $this->requirement = Requirement::query()->create([
                    'code' => Requirement::temporaryCode(),
                    'client_id' => $data['client_id'],
                    'crew_chief' => $data['crew_chief'],
                    'district' => $data['district'],
                    'delivery_address' => $data['delivery_address'],
                    'required_date' => $data['required_date'],
                    'notes' => $data['notes'],
                    'created_by' => auth()->id(),
                ]);
                $this->requirement->update(['code' => sprintf('REQ-%05d', $this->requirement->id)]);
            }

            foreach ($data['items'] as $item) {
                $this->requirement->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                ]);
            }
        });

        session()->flash('ok', "Requerimiento {$this->requirement->code} guardado.");
        $this->redirectRoute('requerimientos.ver', ['requirement' => $this->requirement->id], navigate: true);
    }

    public function render(): View
    {
        return view('livewire.requirements.form', [
            'clients' => Client::query()->active()->orderBy('business_name')->get(['id', 'business_name']),
            'products' => Product::query()->active()->orderBy('name')->get(['id', 'name', 'code', 'unit_code', 'stock']),
        ]);
    }
}
