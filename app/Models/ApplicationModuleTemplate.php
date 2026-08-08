<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationModuleTemplate extends Model
{
    use HasFactory;

    protected $table = 'application_module_templates';

    public const EDITOR_TYPES = [
        'word',
        'excel',
        'contenteditable',
    ];

    protected $fillable = [
        'application_module_id',
        'document_type',
        'template_name',
        'content_data',
        'editor_type',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function module(): BelongsTo
    {
        return $this->belongsTo(ApplicationModule::class, 'application_module_id');
    }
}
