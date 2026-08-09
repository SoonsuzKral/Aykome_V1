<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationModuleSubmission extends Model
{
    use HasFactory;

    protected $table = 'application_module_submissions';

    public const STATUSES = [
        'draft',
        'submitted',
        'approved',
        'rejected',
        'revision',
    ];

    protected $fillable = [
        'application_id',
        'application_module_id',
        'status',
        'submitted_at',
        'submitted_by',
        'approved_at',
        'approved_by',
        'notes',
        'rejection_reason',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(ApplicationModule::class, 'application_module_id');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
