<?php

namespace App\Models;

use App\Enums\CreditNoteStatus;
use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'issue_date' => 'date',
            'due_date' => 'date',
            'annulled_at' => 'datetime',
            'has_detraction' => 'boolean',
            'detraction_percent' => 'decimal:2',
            'detraction_amount' => 'decimal:2',
            'taxable_amount' => 'decimal:2',
            'igv' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function series(): BelongsTo
    {
        return $this->belongsTo(Series::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function dispatchGuides(): BelongsToMany
    {
        return $this->belongsToMany(DispatchGuide::class)->withTimestamps();
    }

    public function creditNotes(): HasMany
    {
        return $this->hasMany(CreditNote::class);
    }

    public function receivable(): HasOne
    {
        return $this->hasOne(AccountReceivable::class);
    }

    public function electronicDocument(): MorphOne
    {
        return $this->morphOne(ElectronicDocument::class, 'documentable');
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isDraft(): bool
    {
        return $this->status === InvoiceStatus::Draft;
    }

    /**
     * Nota de crédito vigente (no rechazada) sobre esta factura.
     */
    public function activeCreditNote(): ?CreditNote
    {
        return $this->creditNotes()
            ->where('status', '!=', CreditNoteStatus::Rejected)
            ->latest('id')
            ->first();
    }

    public function scopeFilter(Builder $query, ?int $clientId, ?string $status, ?string $from, ?string $until): Builder
    {
        return $query
            ->when($clientId, fn (Builder $q) => $q->where('client_id', $clientId))
            ->when($status, fn (Builder $q) => $q->where('status', $status))
            ->when($from, fn (Builder $q) => $q->whereDate('issue_date', '>=', $from))
            ->when($until, fn (Builder $q) => $q->whereDate('issue_date', '<=', $until));
    }
}
