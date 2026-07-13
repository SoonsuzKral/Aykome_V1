<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public $timestamps = false;          // only created_at, managed by DB default

    protected $fillable = [
        'user_id',
        'user_name',
        'user_role',
        'action',
        'subject_type',
        'subject_id',
        'description',
        'ip_address',
        'user_agent',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta'       => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Human-readable action labels ─────────────────────────────────────────
    public static function actionLabel(string $action): string
    {
        return match ($action) {
            'auth.login'            => 'Giriş',
            'auth.logout'           => 'Çıkış',
            'tckn.query'            => 'TCKN Sorgusu',
            'application.create'    => 'Başvuru Oluşturma',
            'application.update'    => 'Başvuru Güncelleme',
            'application.submit'    => 'Başvuru Gönderme',
            'price.approve'         => 'Fiyat Onayı',
            'receipt.upload'        => 'Makbuz Yükleme',
            'receipt.approve'       => 'Makbuz Onayı',
            'receipt.reject'        => 'Makbuz Reddi',
            'task.transfer'         => 'Görev Devri',
            'license.create'        => 'Lisans Oluşturma',
            'license.update'        => 'Lisans Güncelleme',
            'license.lock'          => 'Lisans Kilitleme',
            'user.create'           => 'Kullanıcı Oluşturma',
            'user.update'           => 'Kullanıcı Güncelleme',
            default                 => str_replace('.', ' › ', $action),
        };
    }

    // ── Badge color class for action ─────────────────────────────────────────
    public static function actionBadgeClass(string $action): string
    {
        return match (true) {
            str_starts_with($action, 'auth.')        => 'bg-slate-100 text-slate-600',
            str_starts_with($action, 'tckn.')        => 'bg-cyan-100 text-cyan-700',
            str_starts_with($action, 'application.') => 'bg-blue-100 text-blue-700',
            str_starts_with($action, 'price.')        => 'bg-indigo-100 text-indigo-700',
            str_starts_with($action, 'receipt.')      => 'bg-amber-100 text-amber-700',
            str_starts_with($action, 'task.')         => 'bg-purple-100 text-purple-700',
            str_starts_with($action, 'license.')      => 'bg-emerald-100 text-emerald-700',
            str_starts_with($action, 'user.')         => 'bg-rose-100 text-rose-700',
            default                                   => 'bg-slate-100 text-slate-500',
        };
    }
}
