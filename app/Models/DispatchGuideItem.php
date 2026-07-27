<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DispatchGuideItem extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'quantity_requested' => 'decimal:2',
            'quantity_dispatched' => 'decimal:2',
        ];
    }

    public function dispatchGuide(): BelongsTo
    {
        return $this->belongsTo(DispatchGuide::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function difference(): float
    {
        return (float) $this->quantity_requested - (float) $this->quantity_dispatched;
    }
}
