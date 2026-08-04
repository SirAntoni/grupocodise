<?php

namespace App\Models;

use App\Enums\RequirementStatus;
use App\Models\Concerns\GeneratesSequentialCode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Requirement extends Model
{
    use GeneratesSequentialCode, HasFactory, SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => RequirementStatus::class,
            'required_date' => 'date',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RequirementItem::class);
    }

    public function dispatchGuides(): HasMany
    {
        return $this->hasMany(DispatchGuide::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isEditable(): bool
    {
        return $this->status === RequirementStatus::Pending;
    }

    public static function nextCode(): string
    {
        $last = static::withTrashed()->orderByDesc('id')->value('id') ?? 0;

        return sprintf('REQ-%05d', $last + 1);
    }

    public function scopeFilter(Builder $query, ?int $clientId, ?string $status, ?string $from, ?string $until): Builder
    {
        return $query
            ->when($clientId, fn (Builder $q) => $q->where('client_id', $clientId))
            ->when($status, fn (Builder $q) => $q->where('status', $status))
            ->when($from, fn (Builder $q) => $q->whereDate('required_date', '>=', $from))
            ->when($until, fn (Builder $q) => $q->whereDate('required_date', '<=', $until));
    }
}
