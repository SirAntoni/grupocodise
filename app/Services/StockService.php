<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\DispatchGuide;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class StockService
{
    /**
     * Entrada de mercadería al almacén.
     */
    public function registerEntry(Product $product, float $quantity, ?string $notes = null, ?User $user = null): StockMovement
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('La cantidad de una entrada debe ser mayor a cero.');
        }

        return $this->apply($product, StockMovementType::Entry, $quantity, null, $notes, $user);
    }

    /**
     * Ajuste manual de inventario; la cantidad puede ser negativa.
     */
    public function registerAdjustment(Product $product, float $quantity, ?string $notes = null, ?User $user = null): StockMovement
    {
        if ($quantity == 0.0) {
            throw new \InvalidArgumentException('La cantidad del ajuste no puede ser cero.');
        }

        return $this->apply($product, StockMovementType::Adjustment, $quantity, null, $notes, $user);
    }

    /**
     * Descuenta el stock por la cantidad DESPACHADA de cada ítem al emitir la guía.
     *
     * @throws InsufficientStockException si algún ítem dejaría stock negativo.
     */
    public function dispatchGuideItems(DispatchGuide $guide, ?User $user = null): void
    {
        // Los ítems que no se despacharon (cantidad 0) no mueven stock: no salió
        // nada del almacén, así que no generan movimiento de kardex.
        foreach ($guide->dispatchedItems()->with('product')->get() as $item) {
            $this->apply(
                $item->product,
                StockMovementType::DispatchExit,
                -(float) $item->quantity_dispatched,
                $guide,
                "Guía {$guide->full_number}",
                $user,
            );
        }
    }

    /**
     * Restituye el stock de todos los ítems al anular una guía emitida.
     */
    public function restituteGuideItems(DispatchGuide $guide, ?User $user = null): void
    {
        // Solo se restituye lo que efectivamente se descontó al emitir.
        foreach ($guide->dispatchedItems()->with('product')->get() as $item) {
            $this->apply(
                $item->product,
                StockMovementType::AnnulmentRestitution,
                (float) $item->quantity_dispatched,
                $guide,
                "Anulación de guía {$guide->full_number}",
                $user,
            );
        }
    }

    /**
     * Aplica un movimiento con bloqueo pesimista sobre el producto y deja
     * rastro en el kardex. $signedQuantity ya trae el sentido del movimiento.
     */
    protected function apply(
        Product $product,
        StockMovementType $type,
        float $signedQuantity,
        ?Model $reference,
        ?string $notes,
        ?User $user,
    ): StockMovement {
        return DB::transaction(function () use ($product, $type, $signedQuantity, $reference, $notes, $user) {
            /** @var Product $locked */
            $locked = Product::query()->whereKey($product->getKey())->lockForUpdate()->firstOrFail();

            $before = (float) $locked->stock;
            $after = round($before + $signedQuantity, 2);

            if ($after < 0) {
                throw new InsufficientStockException($locked, abs($signedQuantity), $before);
            }

            $locked->forceFill(['stock' => $after])->save();

            $movement = $locked->stockMovements()->create([
                // El ajuste conserva el signo; el resto se registra en positivo.
                'quantity' => $type === StockMovementType::Adjustment ? $signedQuantity : abs($signedQuantity),
                'type' => $type,
                'stock_before' => $before,
                'stock_after' => $after,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'notes' => $notes,
                'user_id' => $user?->id ?? auth()->id(),
            ]);

            $product->refresh();

            return $movement;
        });
    }
}
