<?php

namespace App\Enums;

enum ApplicationStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case PreExcavationApproved = 'pre_excavation_approved';
    case Pending = 'pending';
    case PreApproved = 'pre_approved';
    case MeasurementDone = 'measurement_done';
    case Accrued = 'accrued';
    case Priced = 'priced';
    case AwaitingPayment = 'awaiting_payment';
    case ReceiptPending = 'receipt_pending';
    // KATI ADIM KAPISI ara durumları (belediye yetkisiyle manuel modül açma)
    case ExcavationCompleted = 'excavation_completed';
    case MetragePending = 'metrage_pending';
    // Belediye metrajı hazırlayıp "Kuruma Gönder" dediğinde geçilir → alt kurum Step 3'ü ilk kez burada görür
    case MetrageSent = 'metrage_sent';
    case MetrageRevision = 'metrage_revision';
    case MetrageApproved = 'metrage_approved';
    case TahakkukPending = 'tahakkuk_pending';
    // Belediye imzalı tahakkuk/makbuzu "Kuruma Gönder" dediğinde geçilir → alt kurum Step 4'ü ilk kez burada görür
    case TahakkukSent = 'tahakkuk_sent';
    // GÖREV 5: Belediye "TAAHHÜTNAME MODÜLÜNÜ AÇ" dediğinde taahhutname_pending; "Kuruma Gönder" dediğinde taahhutname_sent
    case TaahhutnamePending = 'taahhutname_pending';
    case TaahhutnameSent = 'taahhutname_sent';
    // GÖREV 4: Belediye ruhsat PDF'i üretip (licensed=hazırlık) "İmzala & Kuruma Gönder" dediğinde ruhsat_sent → kurum Step 6'yı burada görür
    case RuhsatSent = 'ruhsat_sent';
    case PaymentCompleted = 'payment_completed';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Licensed = 'licensed';
    case FieldWork = 'field_work';
    case Completed = 'completed';
    case Archived = 'archived';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft                  => 'Taslak',
            self::Submitted              => 'Gönderildi',
            self::PreExcavationApproved  => 'Ön Kazı Onaylı',
            self::Pending                => 'Yeni Başvuru',
            self::PreApproved            => 'Ön Kazı Çıktı',
            self::MeasurementDone        => 'Metraj Güncellendi',
            self::Accrued                => 'Tahakkuk Edildi',
            self::Priced                 => 'Fiyatlandı',
            self::AwaitingPayment        => 'Ödeme Bekliyor',
            self::ReceiptPending         => 'Makbuz Bekliyor',
            self::ExcavationCompleted    => 'Kazı Tamamlandı',
            self::MetragePending         => 'Metraj Açıldı',
            self::MetrageSent            => 'Metraj Kurum Onayında',
            self::MetrageRevision        => 'Metraj Revizyon',
            self::MetrageApproved        => 'Metraj Onaylı',
            self::TahakkukPending        => 'Tahakkuk & Makbuz Açıldı',
            self::TahakkukSent           => 'Tahakkuk & Makbuz Kurumda',
            self::TaahhutnamePending     => 'Taahhütname Açıldı',
            self::TaahhutnameSent        => 'Taahhütname Kurumda',
            self::RuhsatSent             => 'Ruhsat Kuruma Gönderildi',
            self::PaymentCompleted       => 'Ödeme Tamamlandı',
            self::Approved               => 'Onaylandı',
            self::Rejected               => 'Reddedildi',
            self::Licensed               => 'Ruhsatlı',
            self::FieldWork              => 'Saha İşi',
            self::Completed              => 'Tamamlandı',
            self::Archived               => 'Arşiv',
            self::Cancelled              => 'İptal Edildi',
        };
    }

    public static function workflowStep(string $status, bool $isMunicipality = false): array
    {
        if ($isMunicipality) {
            return match ($status) {
                'pending', 'submitted', 'draft',
                'priced', 'awaiting_payment',
                'receipt_pending'  => ['step' => 1, 'label' => 'Tahakkuk & Tahsilat Fişi', 'icon' => '🧾', 'module' => 'tahakkuk'],
                'pre_approved',
                'pre_excavation_approved',
                'measurement_done' => ['step' => 2, 'label' => 'Kazı Metraj Bilgi',        'icon' => '📐', 'module' => 'metraj'],
                'excavation_completed',
                'metrage_pending',
                'metrage_sent',
                'metrage_revision',
                'metrage_approved' => ['step' => 2, 'label' => 'Kazı Metraj Bilgi',        'icon' => '📐', 'module' => 'metraj'],
                'accrued',
                'approved',
                'taahhutname_pending',
                'taahhutname_sent' => ['step' => 3, 'label' => 'Taahhütname İmza',         'icon' => '✍️', 'module' => 'taahhut'],
                'tahakkuk_pending',
                'tahakkuk_sent',
                'payment_completed' => ['step' => 1, 'label' => 'Tahakkuk & Tahsilat Fişi', 'icon' => '🧾', 'module' => 'tahakkuk'],
                'licensed',
                'field_work',
                'ruhsat_sent',
                'completed'        => ['step' => 4, 'label' => 'Ruhsat Çıktısı',           'icon' => '📜', 'module' => 'ruhsat'],
                'cancelled'        => ['step' => 0, 'label' => 'İptal Edildi',             'icon' => '❌', 'module' => ''],
                default            => ['step' => 0, 'label' => 'Beklemede',                 'icon' => '⏳', 'module' => ''],
            };
        }

        return match ($status) {
            // KURAL 1 (Workflow Lock): Alt kurum başvuruyu oluşturur, ÜST YAZI adımı en öndedir.
            // Belediye Ön Kazı'yı üretince (pre_approved) Üst Yazı kilitlenir; red/revize → submitted → tekrar açılır.
            'pending', 'submitted', 'draft'
                                 => ['step' => 1, 'label' => 'Üst Yazı',              'icon' => '✉️', 'module' => 'cover_letter'],
            'pre_approved',
            'pre_excavation_approved'
                                 => ['step' => 2, 'label' => 'Ön Kazı',               'icon' => '⛏️', 'module' => 'on-kazi'],
            'excavation_completed'
                                 => ['step' => 2, 'label' => 'Ön Kazı',               'icon' => '⛏️', 'module' => 'on-kazi'],
            'metrage_pending',
            'metrage_sent',
            'metrage_revision',
            'metrage_approved',
            'measurement_done',
            'priced',
            'awaiting_payment',
            'receipt_pending'    => ['step' => 3, 'label' => 'Saha Metraj',           'icon' => '📐', 'module' => 'metraj'],
            // Tahakkuk & Makbuz: belediye fiyatlandırdı / makbuz beklentisi
            // tahakkuk_sent: belediye imzalı evrakı kuruma gönderdi → alt kurum Step 4'ü ilk kez burada görür
            'tahakkuk_pending',
            'tahakkuk_sent',
            'accrued',
            'approved',
            'payment_completed'  => ['step' => 4, 'label' => 'Tahakkuk & Makbuz',     'icon' => '🧾', 'module' => 'tahakkuk'],
            // Taahhütname imzası (GÖREV 5): belediye modülü açar (taahhutname_pending, kuruma gizli),
            // "Kuruma Gönder" dediğinde taahhutname_sent → alt kurum Step 5'i ilk kez burada görür.
            'taahhutname_pending',
            'taahhutname_sent'   => ['step' => 5, 'label' => 'Taahhütname',           'icon' => '✍️', 'module' => 'taahhut'],
            // Ruhsat (GÖREV 4): licensed belediye hazırlığıdır (kuruma gizli); belediye
            // "İmzala & Kuruma Gönder" dediğinde ruhsat_sent → alt kurum Step 6'yı ilk kez burada görür.
            'licensed',
            'ruhsat_sent',
            'field_work',
            'completed'          => ['step' => 6, 'label' => 'Ruhsat',                'icon' => '📜', 'module' => 'ruhsat'],
            'cancelled'          => ['step' => 0, 'label' => 'İptal Edildi',          'icon' => '❌', 'module' => ''],
            default              => ['step' => 0, 'label' => 'Beklemede',             'icon' => '⏳', 'module' => ''],
        };
    }

    public static function workflowSteps(bool $isMunicipality = false): array
    {
        if ($isMunicipality) {
            return [
                ['status' => 'submitted',   'label' => 'Tahakkuk & Tahsilat Fişi', 'icon' => '🧾'],
                ['status' => 'measurement_done', 'label' => 'Kazı Metraj Bilgi',    'icon' => '📐'],
                ['status' => 'accrued',      'label' => 'Taahhütname İmza',        'icon' => '✍️'],
                ['status' => 'licensed',     'label' => 'Ruhsat Çıktısı',          'icon' => '📜'],
            ];
        }

        return [
            ['status' => 'pending',          'label' => 'Üst Yazı',              'icon' => '✉️'],
            ['status' => 'pre_approved',     'label' => 'Ön Kazı',               'icon' => '⛏️'],
            ['status' => 'measurement_done', 'label' => 'Saha Metraj',           'icon' => '📐'],
            ['status' => 'accrued',          'label' => 'Tahakkuk & Makbuz',     'icon' => '🧾'],
            ['status' => 'approved',         'label' => 'Taahhütname',           'icon' => '✍️'],
            ['status' => 'licensed',         'label' => 'Ruhsat',                'icon' => '📜'],
        ];
    }
}
