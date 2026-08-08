<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationModuleSequence extends Model
{
    use HasFactory;

    protected $table = 'application_module_sequences';

    protected $fillable = [
        'application_module_id',
        'application_type',
        'sort_order',
        'config',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'config' => 'array',
    ];

    public function applicationModule(): BelongsTo
    {
        return $this->belongsTo(ApplicationModule::class, 'application_module_id');
    }

    public function getDependsOn(): ?int
    {
        return $this->config['depends_on'] ?? null;
    }

    public function setDependsOn(?int $moduleId): void
    {
        $config = $this->config ?? [];
        $config['depends_on'] = $moduleId;
        $this->config = $config;
    }
}
