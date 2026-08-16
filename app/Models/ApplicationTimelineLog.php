<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationTimelineLog extends Model
{
    protected $fillable = [
        'application_id',
        'user_id',
        'action',
        'meta',
        'message',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Makine anahtarı 'action' değerini (ör. 'application.submitted') Türkçe
     * başlığa çevirir. Bazı çağrılar (ör. EImzaService::tamamla(),
     * ProcessEngine::approve()) 'action' alanına zaten hazır Türkçe cümle
     * yazıyor — bu durumda haritada eşleşme bulunmaz ve değer OLDUĞU GİBİ
     * döner (zaten Türkçe). Bilinmeyen/gelecekteki makine anahtarları için
     * son çare: nokta/alt çizgiyi boşluğa çevirip ilk harfleri büyütür.
     */
    public static function actionLabel(string $action): string
    {
        $map = [
            'application.created'                    => 'Başvuru Oluşturuldu',
            'application.additional_permit_created'  => 'Ek Ruhsat Başvurusu Oluşturuldu',
            'application.submitted'                  => 'Başvuru Belediyeye Gönderildi',
            'application.metrage_rejected'            => 'Metraj Reddedildi',
            'price.approved'                          => 'Fiyat Onaylandı',
            'receipt.uploaded'                        => 'Makbuz Yüklendi',
            'receipt.rejected'                        => 'Makbuz Reddedildi',
            'receipt.approved'                        => 'Makbuz Onaylandı',
            'task.assigned'                           => 'Görev Atandı',
            'institution.transferred'                 => 'Kurum Değiştirildi',
            'approval.step'                           => 'Onay Adımı İlerledi',
            'pre_excavation.approved'                 => 'Ön Kazı Onayı Verildi',
        ];

        if (isset($map[$action])) {
            return $map[$action];
        }

        // Zaten Türkçe/okunabilir bir cümleyse (boşluk içeriyorsa) olduğu gibi bırak.
        if (str_contains($action, ' ')) {
            return $action;
        }

        // Bilinmeyen makine anahtarı (ör. 'foo.bar_baz') -> "Foo Bar Baz".
        return ucwords(str_replace(['.', '_'], ' ', $action));
    }
}
