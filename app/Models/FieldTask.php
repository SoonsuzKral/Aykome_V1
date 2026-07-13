<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FieldTask extends Model
{
    protected $fillable = [
        'application_id',
        'assigned_to',
        'assigned_by',
        'status',
        'due_date',
        'notes',
        'stage_1_status',
        'stage_1_notes',
        'stage_1_inspected_at',
        'stage_2_status',
        'stage_2_notes',
        'stage_2_inspected_at',
        'stage_3_status',
        'stage_3_notes',
        'stage_3_inspected_at',
    ];

    protected function casts(): array
    {
        return [
            'due_date'             => 'date',
            'stage_1_inspected_at' => 'datetime',
            'stage_2_inspected_at' => 'datetime',
            'stage_3_inspected_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function stepMedia(): HasMany
    {
        return $this->hasMany(FieldTaskMedia::class);
    }
}
