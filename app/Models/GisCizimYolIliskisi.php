<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GisCizimYolIliskisi extends Model
{
    protected $table = 'gis_cizim_yol_iliskisi';

    protected $fillable = [
        'cizim_id',
        'hat_kimligi',
        'yol_adi',
        'yol_turu',
        'mahalle',
        'ilce',
        'genislik',
        'uzunluk',
        'sorumluluk',
        'properties',
    ];

    protected $casts = [
        'hat_kimligi' => 'integer',
        'genislik' => 'float',
        'uzunluk' => 'float',
        'properties' => 'array',
    ];

    public function cizim()
    {
        return $this->belongsTo(GisCizim::class, 'cizim_id');
    }
}
