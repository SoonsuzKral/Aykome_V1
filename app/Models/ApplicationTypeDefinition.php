<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApplicationTypeDefinition extends Model
{
    use HasFactory;

    protected $table = 'application_type_definitions';

    protected $fillable = [
        'slug',
        'name',
        'description',
        'is_active',
        'config',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'config' => 'array',
    ];

    public function sequences(): HasMany
    {
        return $this->hasMany(ApplicationModuleSequence::class, 'application_type', 'slug');
    }
}
