<?php

namespace App\Livewire\Products;

use App\Models\Product;
use App\Services\StockService;
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

    public string $statusFilter = 'activos';

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $code = '';

    public string $name = '';

    public string $unit_code = 'NIU';

    public ?string $initial_stock = null;

    protected function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:30', Rule::unique('products', 'code')->ignore($this->editingId)],
            'name' => ['required', 'string', 'max:255'],
            'unit_code' => ['required', Rule::in(array_keys(SunatCatalogs::UNITS))],
            'initial_stock' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    protected array $validationAttributes = [
        'code' => 'código',
        'name' => 'nombre',
        'unit_code' => 'unidad de medida',
        'initial_stock' => 'stock inicial',
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->authorize('create', Product::class);
        $this->reset(['editingId', 'code', 'name', 'initial_stock']);
        $this->unit_code = 'NIU';
        $this->resetValidation();
        $this->showForm = true;
    }

    public function openEdit(int $productId): void
    {
        $product = Product::query()->findOrFail($productId);
        $this->authorize('update', $product);

        $this->editingId = $product->id;
        $this->code = $product->code;
        $this->name = $product->name;
        $this->unit_code = $product->unit_code;
        $this->initial_stock = null;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(StockService $stock): void
    {
        $data = $this->validate();

        if ($this->editingId) {
            $product = Product::query()->findOrFail($this->editingId);
            $this->authorize('update', $product);
            $product->update([
                'code' => $data['code'],
                'name' => $data['name'],
                'unit_code' => $data['unit_code'],
            ]);
            session()->now('ok', 'Producto actualizado.');
        } else {
            $this->authorize('create', Product::class);
            $product = Product::query()->create([
                'code' => $data['code'],
                'name' => $data['name'],
                'unit_code' => $data['unit_code'],
            ]);

            if ((float) ($data['initial_stock'] ?? 0) > 0) {
                $stock->registerEntry($product, (float) $data['initial_stock'], 'Stock inicial');
            }
            session()->now('ok', 'Producto registrado.');
        }

        $this->showForm = false;
    }

    public function toggleActive(int $productId): void
    {
        $product = Product::query()->findOrFail($productId);
        $this->authorize('update', $product);

        $product->update(['is_active' => ! $product->is_active]);
        session()->now('ok', $product->is_active ? 'Producto activado.' : 'Producto desactivado.');
    }

    public function delete(int $productId): void
    {
        $product = Product::query()->findOrFail($productId);

        if ($product->hasMovements()) {
            session()->now('error', 'El producto tiene movimientos: desactívalo en lugar de eliminarlo.');

            return;
        }

        $this->authorize('delete', $product);
        $product->delete();
        session()->now('ok', 'Producto eliminado.');
    }

    public function render(): View
    {
        $products = Product::query()
            ->when($this->search !== '', fn ($q) => $q->search($this->search))
            ->when($this->statusFilter === 'activos', fn ($q) => $q->where('is_active', true))
            ->when($this->statusFilter === 'inactivos', fn ($q) => $q->where('is_active', false))
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.products.index', [
            'products' => $products,
            'units' => SunatCatalogs::UNITS,
        ]);
    }
}
