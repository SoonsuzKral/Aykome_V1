<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SurfaceType extends Model
{
    protected $fillable = [
        'name',
        'price_per_m2',
        'active',
        'color_code',
    ];

    protected function casts(): array
    {
        return [
            'price_per_m2' => 'decimal:2',
            'active' => 'boolean',
        ];
    }

    public function applicationSurfaceAreas(): HasMany
    {
        return $this->hasMany(ApplicationSurfaceArea::class);
    }
}
