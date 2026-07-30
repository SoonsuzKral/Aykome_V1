<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExtraPermit extends Model
{
    protected $fillable = [
        'application_id',
        'ek_metraj_m',
        'surface_lines',
        'total_price',
        'deposit_amount',
        'ruhsat_no',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'ek_metraj_m' => 'decimal:2',
            'total_price' => 'decimal:3',
            'deposit_amount' => 'decimal:3',
            'surface_lines' => 'array',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
