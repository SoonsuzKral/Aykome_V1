<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GisCizim extends Model
{
    protected $table = 'gis_cizimler';

    protected $fillable = [
        'user_id',
        'tip',
        'geometri',
        'basvuru_id',
        'lat',
        'lng',
        'aciklama',
    ];

    protected $casts = [
        'geometri' => 'array',
        'lat' => 'float',
        'lng' => 'float',
    ];

    public function application()
    {
        return $this->belongsTo(Application::class, 'basvuru_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function yolIliskileri()
    {
        return $this->hasMany(GisCizimYolIliskisi::class, 'cizim_id');
    }
}
