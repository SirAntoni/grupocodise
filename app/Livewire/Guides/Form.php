<?php

namespace App\Livewire\Guides;

use App\Exceptions\InsufficientStockException;
use App\Models\DispatchGuide;
use App\Models\Product;
use App\Services\DispatchGuideService;
use App\Support\SunatCatalogs;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Form extends Component
{
    public ?DispatchGuide $guide = null;

    public ?string $transfer_date = null;

    public ?int $purchase_order_id = null;

    public string $transfer_reason_code = '01';

    public ?string $transport_mode = null;

    public ?string $carrier_ruc = null;

    public ?string $carrier_name = null;

    public ?string $vehicle_plate = null;

    public ?string $driver_first_names = null;

    public ?string $driver_last_names = null;

    public string $driver_document_type = '1';

    public ?string $driver_document = null;

    public ?string $driver_license = null;

    public ?string $delivery_address = null;

    public ?string $delivery_ubigeo = null;

    public ?string $district = null;

    public ?string $crew_chief = null;

    public ?string $total_weight = null;

    public ?string $packages_count = null;

    public ?string $notes = null;

    /** @var array<int, array{id: int|null, product_id: int|null, quantity_requested: string|null, quantity_dispatched: string|null}> */
    public array $items = [];

    public function mount(int $dispatchGuide): void
    {
        // La creación del borrador es una acción explícita (POST) desde el
        // requerimiento; este formulario solo edita borradores existentes.
        $this->guide = DispatchGuide::query()->with('items')->findOrFail($dispatchGuide);
        $this->authorize('update', $this->guide);

        $this->fillFromGuide();
    }

    protected function fillFromGuide(): void
    {
        $this->transfer_date = $this->guide->transfer_date?->format('Y-m-d');
        $this->purchase_order_id = $this->guide->purchase_order_id;
        $this->transfer_reason_code = $this->guide->transfer_reason_code;
        $this->transport_mode = $this->guide->transport_mode?->value;
        $this->carrier_ruc = $this->guide->carrier_ruc;
        $this->carrier_name = $this->guide->carrier_name;
        $this->vehicle_plate = $this->guide->vehicle_plate;
        $this->driver_first_names = $this->guide->driver_first_names;
        $this->driver_last_names = $this->guide->driver_last_names;
        $this->driver_document_type = $this->guide->driver_document_type ?? '1';
        $this->driver_document = $this->guide->driver_document;
        $this->driver_license = $this->guide->driver_license;
        $this->delivery_address = $this->guide->delivery_address;
        $this->delivery_ubigeo = $this->guide->delivery_ubigeo;
        $this->district = $this->guide->district;
        $this->crew_chief = $this->guide->crew_chief;
        $this->total_weight = $this->guide->total_weight !== null ? (string) $this->guide->total_weight : null;
        $this->packages_count = $this->guide->packages_count !== null ? (string) $this->guide->packages_count : null;
        $this->notes = $this->guide->notes;
        $this->items = $this->guide->items
            ->map(fn ($item) => [
                'key' => (string) \Illuminate\Support\Str::uuid(),
                'id' => $item->id,
                'product_id' => $item->product_id,
                'quantity_requested' => (string) $item->quantity_requested,
                'quantity_dispatched' => (string) $item->quantity_dispatched,
            ])->all();
    }

    protected function rules(): array
    {
        return [
            'transfer_date' => ['required', 'date'],
            'purchase_order_id' => [
                'nullable',
                Rule::exists('purchase_orders', 'id')->where('client_id', $this->guide?->client_id),
            ],
            'transfer_reason_code' => ['required', Rule::in(array_keys(SunatCatalogs::TRANSFER_REASONS))],
            'transport_mode' => ['required', Rule::in(['publico', 'privado'])],
            'carrier_ruc' => [Rule::requiredIf($this->transport_mode === 'publico'), 'nullable', 'digits:11'],
            'carrier_name' => [Rule::requiredIf($this->transport_mode === 'publico'), 'nullable', 'string', 'max:255'],
            'vehicle_plate' => [Rule::requiredIf($this->transport_mode === 'privado'), 'nullable', 'string', 'max:10'],
            'driver_first_names' => [Rule::requiredIf($this->transport_mode === 'privado'), 'nullable', 'string', 'max:255'],
            'driver_last_names' => [Rule::requiredIf($this->transport_mode === 'privado'), 'nullable', 'string', 'max:255'],
            'driver_document_type' => [Rule::requiredIf($this->transport_mode === 'privado'), Rule::in(['1', '4', '7'])],
            'driver_document' => [Rule::requiredIf($this->transport_mode === 'privado'), 'nullable', 'string', 'max:15'],
            'driver_license' => [Rule::requiredIf($this->transport_mode === 'privado'), 'nullable', 'string', 'max:15'],
            'delivery_address' => ['required', 'string', 'max:255'],
            'delivery_ubigeo' => ['nullable', 'digits:6'],
            'district' => ['required', 'string', 'max:100'],
            'crew_chief' => ['required', 'string', 'max:255'],
            'total_weight' => ['required', 'numeric', 'gt:0'],
            'packages_count' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id', 'distinct'],
            'items.*.quantity_requested' => ['required', 'numeric', 'gt:0'],
            'items.*.quantity_dispatched' => ['required', 'numeric', 'gte:0'],
        ];
    }

    protected array $validationAttributes = [
        'transfer_date' => 'fecha de traslado',
        'transfer_reason_code' => 'motivo de traslado',
        'transport_mode' => 'modalidad de transporte',
        'carrier_ruc' => 'RUC del transportista',
        'carrier_name' => 'transportista',
        'vehicle_plate' => 'placa del vehículo',
        'driver_first_names' => 'nombres del conductor',
        'driver_last_names' => 'apellidos del conductor',
        'driver_document_type' => 'tipo de documento del conductor',
        'driver_document' => 'documento del conductor',
        'driver_license' => 'licencia del conductor',
        'delivery_address' => 'dirección de llegada',
        'delivery_ubigeo' => 'ubigeo de llegada',
        'district' => 'distrito',
        'crew_chief' => 'jefe de cuadrilla',
        'total_weight' => 'peso bruto total (kg)',
        'packages_count' => 'número de bultos',
        'items.*.product_id' => 'producto',
        'items.*.quantity_requested' => 'cantidad solicitada',
        'items.*.quantity_dispatched' => 'cantidad despachada',
    ];

    /** Autocompleta nombres y apellidos del conductor desde su DNI (API Migo). */
    public function lookupDriver(\App\Services\MigoService $migo): void
    {
        $this->authorize('update', $this->guide);
        $this->resetValidation('driver_document');

        if ($this->driver_document_type !== '1' || ! preg_match('/^\d{8}$/', (string) $this->driver_document)) {
            $this->addError('driver_document', 'La búsqueda requiere tipo DNI y 8 dígitos.');

            return;
        }

        if (! $migo->isConfigured()) {
            $this->addError('driver_document', 'La búsqueda automática no está disponible; digita los datos manualmente.');

            return;
        }

        $info = $migo->lookupDni($this->driver_document);

        if ($info === null) {
            $this->addError('driver_document', 'No se encontró el DNI (o el servicio no respondió); digita los datos manualmente.');

            return;
        }

        $this->driver_first_names = $info['nombres'];
        $this->driver_last_names = $info['apellidos'];
        session()->now('ok', "Conductor encontrado: {$info['nombre']}. Verifica la separación de nombres y apellidos.");
    }

    /** Autocompleta la razón social del transportista desde su RUC (API Migo). */
    public function lookupCarrier(\App\Services\MigoService $migo): void
    {
        $this->authorize('update', $this->guide);
        $this->resetValidation('carrier_ruc');

        if (! preg_match('/^\d{11}$/', (string) $this->carrier_ruc)) {
            $this->addError('carrier_ruc', 'Digita los 11 dígitos del RUC antes de buscar.');

            return;
        }

        if (! $migo->isConfigured()) {
            $this->addError('carrier_ruc', 'La búsqueda automática no está disponible; digita los datos manualmente.');

            return;
        }

        $info = $migo->lookupRuc($this->carrier_ruc);

        if ($info === null) {
            $this->addError('carrier_ruc', 'No se encontró el RUC (o el servicio no respondió); digita los datos manualmente.');

            return;
        }

        $this->carrier_name = $info['razon_social'];
    }

    public function addItem(): void
    {
        $this->items[] = [
            'key' => (string) \Illuminate\Support\Str::uuid(),
            'id' => null,
            'product_id' => null,
            'quantity_requested' => null,
            'quantity_dispatched' => null,
        ];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function saveDraft(): void
    {
        $this->persist();
        session()->flash('ok', 'Borrador guardado.');
        $this->redirectRoute('guias.ver', ['dispatchGuide' => $this->guide->id], navigate: true);
    }

    public function issue(DispatchGuideService $service): void
    {
        $this->persist();
        $this->authorize('issue', $this->guide);

        try {
            $service->issue($this->guide, auth()->user());
        } catch (InsufficientStockException|\InvalidArgumentException $e) {
            session()->now('error', $e->getMessage());

            return;
        }

        session()->flash('ok', "Guía {$this->guide->full_number} emitida y en cola de envío a SUNAT.");
        $this->redirectRoute('guias.ver', ['dispatchGuide' => $this->guide->id], navigate: true);
    }

    protected function persist(): void
    {
        $this->authorize('update', $this->guide);
        $data = $this->validate();

        DB::transaction(function () use ($data) {
            $this->guide->update([
                'transfer_date' => $data['transfer_date'],
                'purchase_order_id' => $data['purchase_order_id'] ?: null,
                'transfer_reason_code' => $data['transfer_reason_code'],
                'transport_mode' => $data['transport_mode'],
                'carrier_ruc' => $data['carrier_ruc'],
                'carrier_name' => $data['carrier_name'],
                'vehicle_plate' => $data['vehicle_plate'],
                'driver_first_names' => $data['driver_first_names'],
                'driver_last_names' => $data['driver_last_names'],
                'driver_document_type' => $data['driver_document_type'],
                'driver_document' => $data['driver_document'],
                'driver_license' => $data['driver_license'],
                'delivery_address' => $data['delivery_address'],
                'delivery_ubigeo' => $data['delivery_ubigeo'],
                'district' => $data['district'],
                'crew_chief' => $data['crew_chief'],
                'total_weight' => $data['total_weight'],
                'packages_count' => $data['packages_count'] ?: null,
                'notes' => $data['notes'],
            ]);

            $products = Product::query()->findMany(collect($data['items'])->pluck('product_id'))->keyBy('id');

            $this->guide->items()->delete();
            foreach ($data['items'] as $item) {
                $product = $products[$item['product_id']];
                $this->guide->items()->create([
                    'product_id' => $item['product_id'],
                    'description' => $product->name,
                    'unit_code' => $product->unit_code,
                    'quantity_requested' => $item['quantity_requested'],
                    'quantity_dispatched' => $item['quantity_dispatched'],
                ]);
            }
        });

        $this->guide->refresh()->load('items');
    }

    public function render(): View
    {
        return view('livewire.guides.form', [
            'products' => Product::query()->active()->orderBy('name')->get(['id', 'name', 'code', 'unit_code', 'stock']),
            'purchaseOrders' => \App\Models\PurchaseOrder::query()
                ->where('client_id', $this->guide?->client_id)
                ->latest('date')
                ->get(['id', 'number', 'date']),
            'transferReasons' => SunatCatalogs::TRANSFER_REASONS,
        ]);
    }
}
