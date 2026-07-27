<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'stock' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function requirementItems(): HasMany
    {
        return $this->hasMany(RequirementItem::class);
    }

    public function dispatchGuideItems(): HasMany
    {
        return $this->hasMany(DispatchGuideItem::class);
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function quotationItems(): HasMany
    {
        return $this->hasMany(QuotationItem::class);
    }

    /**
     * Un producto con movimientos o referencias documentales no debe
     * eliminarse: se desactiva.
     */
    public function hasMovements(): bool
    {
        return $this->stockMovements()->exists()
            || $this->requirementItems()->exists()
            || $this->dispatchGuideItems()->exists()
            || $this->invoiceItems()->exists()
            || $this->quotationItems()->exists();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->where('name', 'like', "%{$term}%")
            ->orWhere('code', 'like', "%{$term}%"));
    }
}
