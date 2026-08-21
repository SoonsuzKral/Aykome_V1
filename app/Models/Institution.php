<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Institution extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'color_code',
        'is_municipality',
        'type',
        'authorized_person',
        'tax_number',
        'phone',
        'email',
        'address',
        'logo_path',
        'engineer_name',
        'manager_name',
        'tesis_sorumlusu_adi',
        'mudur_adi',
        'mudur_unvani',
    ];

    protected function casts(): array
    {
        return [
            'is_municipality' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    /**
     * Bu kurum Merkez Belediye mi? (is_municipality=true && slug=belediye)
     * Merkez Belediye için süreç atlanır, tüm modüller baştan açıktır.
     */
    public function isMerkezBelediye(): bool
    {
        return (bool) $this->is_municipality && $this->slug === 'belediye';
    }
}
