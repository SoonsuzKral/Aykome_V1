<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcessDefinition extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
        'is_default',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    public function steps(): HasMany
    {
        return $this->hasMany(ProcessStep::class)
            ->orderBy('step_order')
            ->orderBy('id');
    }
}
