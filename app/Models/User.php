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
        'map_preferences',
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
            'map_preferences'   => 'array',
        ];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * Kullanıcının kişisel WMS katman renkleri.
     * Dönüş: [ 'smpns:MISMAP_NUM_KADASTRO_PARSEL' => '#ef4444', ... ]
     */
    public function getMapColorSettings(?string $layer = null): mixed
    {
        $prefs = $this->map_preferences ?? [];

        if ($layer !== null) {
            return $prefs[$layer] ?? null;
        }

        return $prefs;
    }

    /**
     * Tek bir katmanın rengini kişisel JSON'a yazar (yalnızca '#RRGGBB').
     */
    public function setMapColorSetting(string $layer, string $colorHex): void
    {
        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $colorHex)) {
            return;
        }

        $prefs = $this->map_preferences ?? [];
        $prefs[$layer] = strtoupper($colorHex);

        $this->forceFill(['map_preferences' => $prefs])->save();
    }

    /**
     * Belediye tarafı kullanıcısı mı? (Super Admin + tüm municipality-* rolleri)
     * Süreç & Onay Rotası'ndaki hiyerarşi rolleri (buro/sef/mudur/makam) da
     * bu kontrolden geçer; alt kurum personeli hariç tutulur.
     */
    public function isMunicipalityPersonel(): bool
    {
        if ($this->hasRole('super-admin')) {
            return true;
        }

        return $this->roles
            ->contains(fn ($role) => str_starts_with((string) $role->name, 'municipality-'));
    }

    /**
     * Makam (Başkan/Başkan Yrd.) kullanıcısı mı? Sisteme girişte anasayfası
     * "Makam Masası" olur.
     */
    public function isMakam(): bool
    {
        return $this->hasAnyRole(config('aykome.makam_roles', ['municipality-makam', 'municipality-admin']));
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
