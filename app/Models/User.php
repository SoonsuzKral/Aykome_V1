<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'institution_id',
        'name',
        'email',
        'phone',
        'national_id',
        'password',
        'is_active',
        'is_on_field',
        'current_lat',
        'current_lng',
        'field_started_at',
        'last_seen_lat',
        'last_seen_lng',
        'last_seen_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'field_started_at'  => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
            'is_on_field'       => 'boolean',
            'current_lat'       => 'float',
            'current_lng'       => 'float',
            'last_seen_lat'     => 'float',
            'last_seen_lng'     => 'float',
            'last_seen_at'      => 'datetime',
        ];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function createdApplications(): HasMany
    {
        return $this->hasMany(Application::class, 'created_by');
    }

    public function fieldTasksAssigned(): HasMany
    {
        return $this->hasMany(FieldTask::class, 'assigned_to');
    }
}
