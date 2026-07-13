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
}
