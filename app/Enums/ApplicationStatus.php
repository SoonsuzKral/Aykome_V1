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
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Licensed = 'licensed';
    case FieldWork = 'field_work';
    case Completed = 'completed';
    case Archived = 'archived';

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
            self::Approved               => 'Onaylandı',
            self::Rejected               => 'Reddedildi',
            self::Licensed               => 'Ruhsatlı',
            self::FieldWork              => 'Saha İşi',
            self::Completed              => 'Tamamlandı',
            self::Archived               => 'Arşiv',
        };
    }

    public static function workflowStep(string $status): array
    {
        return match ($status) {
            'pending', 'submitted', 'draft'
                                 => ['step' => 1, 'label' => 'Ön Kazı',              'icon' => '⛏️', 'module' => 'on-kazi'],
            'pre_approved',
            'pre_excavation_approved'
                                 => ['step' => 2, 'label' => 'Saha Metraj',           'icon' => '📐', 'module' => 'metraj'],
            'measurement_done',
            'priced',
            'awaiting_payment',
            'receipt_pending'    => ['step' => 3, 'label' => 'Tahakkuk & Makbuz',     'icon' => '🧾', 'module' => 'tahakkuk'],
            'accrued',
            'approved'           => ['step' => 4, 'label' => 'Taahhütname İmza',      'icon' => '✍️', 'module' => 'taahhut'],
            'licensed',
            'field_work',
            'completed'          => ['step' => 5, 'label' => 'Ruhsat Çıktısı',        'icon' => '📜', 'module' => 'ruhsat'],
            default              => ['step' => 0, 'label' => 'Beklemede',             'icon' => '⏳', 'module' => ''],
        };
    }

    public static function workflowSteps(): array
    {
        return [
            ['status' => 'pending',          'label' => 'Ön Kazı',               'icon' => '⛏️'],
            ['status' => 'pre_approved',     'label' => 'Saha Metraj',           'icon' => '📐'],
            ['status' => 'measurement_done', 'label' => 'Tahakkuk & Makbuz',     'icon' => '🧾'],
            ['status' => 'accrued',          'label' => 'Taahhütname İmza',      'icon' => '✍️'],
            ['status' => 'licensed',         'label' => 'Ruhsat Çıktısı',        'icon' => '📜'],
        ];
    }
}
