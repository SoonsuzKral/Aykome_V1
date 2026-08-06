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
        'module_permissions',
        'personnel_ids',
        'visibility_config',
        'approval_config',
        'signature_config',
        'action_type',
        'step_order',
        'is_active',
        'canvas_x',
        'canvas_y',
    ];

    protected function casts(): array
    {
        return [
            'roles' => 'array',
            'approvable_modules' => 'array',
            'module_permissions' => 'array',
            'personnel_ids' => 'array',
            'visibility_config' => 'array',
            'approval_config' => 'array',
            'signature_config' => 'array',
            'step_order' => 'integer',
            'is_active' => 'boolean',
            'canvas_x' => 'integer',
            'canvas_y' => 'integer',
        ];
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(ProcessDefinition::class, 'process_definition_id');
    }
}
