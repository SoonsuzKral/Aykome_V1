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
    case MetrageRevision = 'metrage_revision';
    case MetrageApproved = 'metrage_approved';
    case TahakkukPending = 'tahakkuk_pending';
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
            self::MetrageRevision        => 'Metraj Revizyon',
            self::MetrageApproved        => 'Metraj Onaylı',
            self::TahakkukPending        => 'Tahakkuk & Makbuz Açıldı',
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
                'metrage_revision',
                'metrage_approved' => ['step' => 2, 'label' => 'Kazı Metraj Bilgi',        'icon' => '📐', 'module' => 'metraj'],
                'accrued',
                'approved'         => ['step' => 3, 'label' => 'Taahhütname İmza',         'icon' => '✍️', 'module' => 'taahhut'],
                'tahakkuk_pending',
                'payment_completed' => ['step' => 1, 'label' => 'Tahakkuk & Tahsilat Fişi', 'icon' => '🧾', 'module' => 'tahakkuk'],
                'licensed',
                'field_work',
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
            'metrage_revision',
            'metrage_approved',
            'measurement_done',
            'priced',
            'awaiting_payment',
            'receipt_pending'    => ['step' => 3, 'label' => 'Saha Metraj',           'icon' => '📐', 'module' => 'metraj'],
            // Tahakkuk & Makbuz: belediye fiyatlandırdı / makbuz beklentisi
            'tahakkuk_pending',
            'accrued',
            'approved',
            'payment_completed'  => ['step' => 4, 'label' => 'Tahakkuk & Makbuz',     'icon' => '🧾', 'module' => 'tahakkuk'],
            // Taahhütname imzası (belediye/kurum) — ruhsat öncesi son hazırlık adımı
            'licensed',
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
