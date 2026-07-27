<?php

namespace App\Models;

use App\Enums\SeriesDocumentType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Series extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'document_type' => SeriesDocumentType::class,
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForType(Builder $query, SeriesDocumentType $type): Builder
    {
        return $query->where('document_type', $type);
    }

    public function formatNumber(int $number): string
    {
        return sprintf('%s-%08d', $this->code, $number);
    }
}
