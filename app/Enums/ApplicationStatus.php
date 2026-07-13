<?php

namespace App\Enums;

enum ApplicationStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case PreExcavationApproved = 'pre_excavation_approved';
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
}
