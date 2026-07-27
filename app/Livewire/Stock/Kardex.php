<?php

namespace App\Livewire\Stock;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\StockService;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Kardex extends Component
{
    use WithPagination;

    #[Url]
    public ?int $productId = null;

    public string $typeFilter = '';

    public ?string $from = null;

    public ?string $until = null;

    public bool $showMovementForm = false;

    public string $movementKind = 'entrada';

    public ?int $movementProductId = null;

    public ?string $quantity = null;

    public ?string $notes = null;

    protected function rules(): array
    {
        return [
            'movementKind' => ['required', Rule::in(['entrada', 'ajuste'])],
            'movementProductId' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'numeric', 'not_in:0'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected array $validationAttributes = [
        'movementProductId' => 'producto',
        'quantity' => 'cantidad',
        'notes' => 'observación',
    ];

    public function updating($property): void
    {
        if (in_array($property, ['productId', 'typeFilter', 'from', 'until'], true)) {
            $this->resetPage();
        }
    }

    public function openMovementForm(string $kind): void
    {
        $this->authorize('stock.manage');
        $this->movementKind = $kind;
        $this->movementProductId = $this->productId;
        $this->reset(['quantity', 'notes']);
        $this->resetValidation();
        $this->showMovementForm = true;
    }

    public function saveMovement(StockService $stock): void
    {
        $this->authorize('stock.manage');
        $data = $this->validate();

        $product = Product::query()->findOrFail($data['movementProductId']);
        $quantity = (float) $data['quantity'];

        try {
            if ($data['movementKind'] === 'entrada') {
                $stock->registerEntry($product, $quantity, $data['notes'] ?? null);
                session()->now('ok', 'Entrada registrada.');
            } else {
                $stock->registerAdjustment($product, $quantity, $data['notes'] ?? null);
                session()->now('ok', 'Ajuste registrado.');
            }
            $this->showMovementForm = false;
        } catch (\InvalidArgumentException|\App\Exceptions\InsufficientStockException $e) {
            $this->addError('quantity', $e->getMessage());
        }
    }

    public function render(): View
    {
        $movements = StockMovement::query()
            ->with(['product', 'user', 'reference'])
            ->when($this->productId, fn ($q) => $q->where('product_id', $this->productId))
            ->when($this->typeFilter !== '', fn ($q) => $q->where('type', $this->typeFilter))
            ->when($this->from, fn ($q) => $q->whereDate('created_at', '>=', $this->from))
            ->when($this->until, fn ($q) => $q->whereDate('created_at', '<=', $this->until))
            ->latest('id')
            ->paginate(20);

        return view('livewire.stock.kardex', [
            'movements' => $movements,
            'products' => Product::query()->orderBy('name')->get(['id', 'name', 'code', 'stock']),
            'types' => StockMovementType::cases(),
        ]);
    }
}
