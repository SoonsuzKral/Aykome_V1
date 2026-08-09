<?php

namespace App\Services;

use App\Models\AuditLog;
use Throwable;

/**
 * Statik sistem denetim kaydı yardımcısı.
 * Kullanım: AuditLogger::log('action', 'description', 'Model', $id, $meta);
 * Hiçbir zaman istisna fırlatmaz — log yazımı uygulamayı asla çökertemez.
 */
final class AuditLogger
{
    public static function log(
        string $action,
        string $description,
        ?string $subjectType = null,
        ?int $subjectId = null,
        array $meta = [],
    ): void {
        try {
            $user = auth()->user();

            AuditLog::query()->create([
                'user_id'      => $user?->id,
                'user_name'    => $user?->name,
                'user_role'    => $user?->getRoleNames()->first(),
                'action'       => $action,
                'subject_type' => $subjectType,
                'subject_id'   => $subjectId,
                'description'  => mb_substr($description, 0, 512),
                'ip_address'   => request()->ip(),
                'user_agent'   => mb_substr(request()->userAgent() ?? '', 0, 512),
                'meta'         => $meta ?: null,
            ]);
        } catch (Throwable) {
            // Audit log hatası hiçbir zaman ana akışı bozmaz.
        }
    }
}
