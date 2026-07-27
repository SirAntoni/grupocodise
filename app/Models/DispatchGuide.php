<?php

namespace App\Models;

use App\Enums\DispatchGuideStatus;
use App\Enums\InvoiceStatus;
use App\Enums\TransportMode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class DispatchGuide extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => DispatchGuideStatus::class,
            'transport_mode' => TransportMode::class,
            'issue_date' => 'date',
            'transfer_date' => 'date',
            'annulled_at' => 'datetime',
            'total_weight' => 'decimal:2',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(Requirement::class);
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
        return $this->hasMany(DispatchGuideItem::class);
    }

    public function invoices(): BelongsToMany
    {
        return $this->belongsToMany(Invoice::class)->withTimestamps();
    }

    public function electronicDocument(): MorphOne
    {
        return $this->morphOne(ElectronicDocument::class, 'documentable');
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function annulledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'annulled_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isDraft(): bool
    {
        return $this->status === DispatchGuideStatus::Draft;
    }

    public function isIssued(): bool
    {
        return $this->status === DispatchGuideStatus::Issued;
    }

    /**
     * Factura activa que ya "ocupa" esta guía (borrador, pendiente o aceptada).
     */
    public function activeInvoice(): ?Invoice
    {
        return $this->invoices()
            ->whereIn('invoices.status', InvoiceStatus::activeStates())
            ->first();
    }

    public function scopeIssued(Builder $query): Builder
    {
        return $query->where('status', DispatchGuideStatus::Issued);
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
