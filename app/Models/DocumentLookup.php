<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentLookup extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'fetched_at' => 'datetime',
        ];
    }
}
