<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
            'method' => PaymentMethod::class,
        ];
    }

    public function receivable(): BelongsTo
    {
        return $this->belongsTo(AccountReceivable::class, 'account_receivable_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeFilter(Builder $query, ?int $clientId, ?int $invoiceId, ?string $from, ?string $until): Builder
    {
        return $query
            ->when($clientId, fn (Builder $q) => $q->whereHas('receivable.invoice', fn (Builder $i) => $i->where('client_id', $clientId)))
            ->when($invoiceId, fn (Builder $q) => $q->whereHas('receivable', fn (Builder $r) => $r->where('invoice_id', $invoiceId)))
            ->when($from, fn (Builder $q) => $q->whereDate('payment_date', '>=', $from))
            ->when($until, fn (Builder $q) => $q->whereDate('payment_date', '<=', $until));
    }
}
