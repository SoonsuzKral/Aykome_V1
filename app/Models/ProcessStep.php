<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcessStep extends Model
{
    protected $fillable = [
        'process_definition_id',
        'name',
        'role_key',
        'roles',
        'approvable_modules',
        'step_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'roles' => 'array',
            'approvable_modules' => 'array',
            'step_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(ProcessDefinition::class, 'process_definition_id');
    }
}
