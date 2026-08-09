<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GisBasvuruNokta extends Model
{
    protected $table = 'gis_basvuru_noktalar';

    protected $fillable = [
        'basvuru_id',
        'basvuru_tipi',
        'lat',
        'lng',
        'ilce',
        'mahalle',
        'ada',
        'parsel',
        'wfs_response',
    ];

    protected $casts = [
        'wfs_response' => 'array',
        'lat' => 'float',
        'lng' => 'float',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'basvuru_id');
    }
}