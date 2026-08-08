<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationModuleField extends Model
{
    use HasFactory;

    protected $table = 'application_module_fields';

    public const FIELD_TYPES = [
        'text',
        'textarea',
        'number',
        'decimal',
        'select',
        'multiselect',
        'checkbox',
        'radio',
        'file',
        'date',
        'datetime',
        'email',
        'phone',
        'address',
    ];

    public const WIDTHS = [
        'full',
        'half',
        'third',
    ];

    protected $fillable = [
        'application_module_id',
        'field_name',
        'field_type',
        'label',
        'placeholder',
        'default_value',
        'help_text',
        'field_options',
        'validation_rules',
        'width',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'field_options' => 'array',
        'validation_rules' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function module(): BelongsTo
    {
        return $this->belongsTo(ApplicationModule::class, 'application_module_id');
    }

    public function getIsRequiredAttribute(): bool
    {
        return in_array('required', $this->validation_rules ?? []);
    }

    public function setIsRequiredAttribute(bool $value): void
    {
        $rules = $this->validation_rules ?? [];
        $rules = array_filter($rules, fn($r) => $r !== 'required');
        if ($value) {
            $rules[] = 'required';
        }
        $this->validation_rules = array_values($rules);
    }
}
