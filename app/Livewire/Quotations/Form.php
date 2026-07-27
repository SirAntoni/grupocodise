<?php

namespace App\Livewire\Quotations;

use App\Models\Client;
use App\Models\Product;
use App\Models\Quotation;
use App\Services\QuotationService;
use App\Support\SunatCatalogs;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Form extends Component
{
    public ?Quotation $quotation = null;

    public ?int $client_id = null;

    public ?string $issue_date = null;

    public ?string $valid_until = null;

    public ?string $notes = null;

    /** @var array<int, array{product_id: int|null, description: string|null, unit_code: string, quantity: string|null, unit_value: string|null}> */
    public array $items = [];

    public function mount(?Quotation $quotation = null): void
    {
        $this->authorize('quotations.manage');

        if ($quotation) {
            $this->quotation = $quotation->load('items');

            if (! $this->quotation->isEditable()) {
                abort(403, 'Solo se puede editar una cotización en estado enviada.');
            }

            $this->client_id = $this->quotation->client_id;
            $this->issue_date = $this->quotation->issue_date->format('Y-m-d');
            $this->valid_until = $this->quotation->valid_until->format('Y-m-d');
            $this->notes = $this->quotation->notes;
            $this->items = $this->quotation->items->map(fn ($item) => [
                'key' => (string) \Illuminate\Support\Str::uuid(),
                'product_id' => $item->product_id,
                'description' => $item->description,
                'unit_code' => $item->unit_code,
                'quantity' => (string) $item->quantity,
                'unit_value' => (string) $item->unit_value,
            ])->all();
        } else {
            $this->issue_date = now()->format('Y-m-d');
            $this->valid_until = now()->addDays(15)->format('Y-m-d');
            $this->items = [$this->emptyItem()];
        }
    }

    protected function emptyItem(): array
    {
        return [
            'key' => (string) \Illuminate\Support\Str::uuid(),
            'product_id' => null,
            'description' => null,
            'unit_code' => 'NIU',
            'quantity' => null,
            'unit_value' => null,
        ];
    }

    protected function rules(): array
    {
        return [
            'client_id' => ['required', 'exists:clients,id'],
            'issue_date' => ['required', 'date'],
            'valid_until' => ['required', 'date', 'after_or_equal:issue_date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'exists:products,id'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.unit_code' => ['required', Rule::in(array_keys(SunatCatalogs::UNITS))],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_value' => ['required', 'numeric', 'min:0'],
        ];
    }

    protected array $validationAttributes = [
        'client_id' => 'cliente',
        'issue_date' => 'fecha de emisión',
        'valid_until' => 'vigencia',
        'items.*.product_id' => 'producto',
        'items.*.description' => 'descripción',
        'items.*.unit_code' => 'unidad',
        'items.*.quantity' => 'cantidad',
        'items.*.unit_value' => 'precio unitario',
    ];

    public function addItem(): void
    {
        $this->items[] = $this->emptyItem();
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    /** Al elegir producto, precarga descripción y unidad. */
    public function updated($property, $value): void
    {
        if (preg_match('/^items\.(\d+)\.product_id$/', (string) $property, $m) && $value) {
            $product = Product::query()->find($value);
            if ($product) {
                $this->items[(int) $m[1]]['description'] = $product->name;
                $this->items[(int) $m[1]]['unit_code'] = $product->unit_code;
            }
        }
    }

    public function save(QuotationService $service): void
    {
        $this->authorize('quotations.manage');
        $data = $this->validate();

        try {
            $this->quotation = $service->save(
                $this->quotation,
                [
                    'client_id' => $data['client_id'],
                    'issue_date' => $data['issue_date'],
                    'valid_until' => $data['valid_until'],
                    'notes' => $data['notes'],
                ],
                $data['items'],
                auth()->user(),
            );
        } catch (\InvalidArgumentException $e) {
            session()->now('error', $e->getMessage());

            return;
        }

        session()->flash('ok', "Cotización {$this->quotation->code} guardada.");
        $this->redirectRoute('cotizaciones.ver', ['quotation' => $this->quotation->id], navigate: true);
    }

    public function render(): View
    {
        return view('livewire.quotations.form', [
            'clients' => Client::query()->active()->orderBy('business_name')->get(['id', 'business_name']),
            'products' => Product::query()->active()->orderBy('name')->get(['id', 'name', 'code', 'unit_code']),
            'units' => SunatCatalogs::UNITS,
        ]);
    }
}
