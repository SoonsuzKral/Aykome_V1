<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ApplicationModule extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'application_modules';

    protected $fillable = [
        'uuid',
        'slug',
        'name',
        'description',
        'icon',
        'color',
        'sort_order',
        'is_active',
        'config',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'config' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function fields(): HasMany
    {
        return $this->hasMany(ApplicationModuleField::class)->orderBy('sort_order');
    }

    public function templates(): HasMany
    {
        return $this->hasMany(ApplicationModuleTemplate::class)->orderBy('sort_order');
    }

    public function sequences(): HasMany
    {
        return $this->hasMany(ApplicationModuleSequence::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(ApplicationModuleSubmission::class);
    }

    public function getConfigValue(string $key, mixed $default = null): mixed
    {
        return data_get($this->config, $key, $default);
    }

    public function setConfigValue(string $key, mixed $value): void
    {
        $config = $this->config ?? [];
        data_set($config, $key, $value);
        $this->config = $config;
    }
}
