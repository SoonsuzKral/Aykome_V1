<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * TAM_WORLD_YAPISI.md — Taslak Kütüphanesi.
 * Global/kurum/başvuru şablon editöründe kullanıcının adlandırıp sakladığı
 * BİRDEN FAZLA şablon sürümü (manuel yazılmış ya da Word'den içe aktarılmış).
 * Aktif (render'da kullanılan) şablonu DEĞİŞTİRMEZ — yalnızca editöre
 * "yükle" için bir kütüphane görevi görür.
 */
class DocumentTemplateDraft extends Model
{
    protected $fillable = [
        'scope',
        'scope_id',
        'document_type',
        'name',
        'content_data',
        'source',
        'created_by',
    ];

    public function scopeFor($query, string $scope, ?int $scopeId, string $documentType)
    {
        return $query->where('scope', $scope)
            ->where('scope_id', $scopeId)
            ->where('document_type', $documentType);
    }
}
