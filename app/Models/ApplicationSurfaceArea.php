<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationSurfaceArea extends Model
{
    protected $fillable = [
        'application_id',
        'surface_type_id',
        'width_m',
        'length_m',
        'quantity',
        'multiplier',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'width_m' => 'decimal:4',
            'length_m' => 'decimal:4',
            'quantity' => 'decimal:4',
            'multiplier' => 'decimal:4',
            'amount' => 'decimal:2',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function surfaceType(): BelongsTo
    {
        return $this->belongsTo(SurfaceType::class);
    }
}
