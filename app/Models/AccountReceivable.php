<?php

namespace App\Models;

use App\Enums\ReceivableStatus;
use App\Enums\TrafficLight;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountReceivable extends Model
{
    use HasFactory;

    protected $table = 'accounts_receivable';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'balance' => 'decimal:2',
            'traffic_light' => TrafficLight::class,
            'status' => ReceivableStatus::class,
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'account_receivable_id');
    }

    public function isOpen(): bool
    {
        return $this->status->isOpen();
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [ReceivableStatus::Pending, ReceivableStatus::Partial]);
    }

    public function scopeFilter(Builder $query, ?int $clientId, ?string $trafficLight): Builder
    {
        return $query
            ->when($clientId, fn (Builder $q) => $q->whereHas('invoice', fn (Builder $i) => $i->where('client_id', $clientId)))
            ->when($trafficLight, fn (Builder $q) => $q->where('traffic_light', $trafficLight));
    }
}
