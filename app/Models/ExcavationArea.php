<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExcavationArea extends Model
{
    protected $fillable = [
        'application_id',
        'polygon_geojson',
        'total_area_m2',
        'center_lat',
        'center_lng',
        'address_text',
    ];

    protected function casts(): array
    {
        return [
            'total_area_m2' => 'decimal:4',
            'center_lat' => 'decimal:8',
            'center_lng' => 'decimal:8',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
