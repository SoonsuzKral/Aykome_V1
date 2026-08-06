<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Kurum bazlı belge şablonu.
 * Alt kurumlar (AKSA, Dicle Elektrik vb.) kendi "Üst Yazı" şablonunu düzenler;
 * bu tablo her kurumun şablonunu yalnızca kendi başvurularında geçerli olacak
 * şekilde saklar. Global (belediye) şablon global_document_templates'ta kalır.
 */
class InstitutionDocumentTemplate extends Model
{
    protected $fillable = [
        'institution_id',
        'document_type',
        'content_data',
        'editor_type',
    ];

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }
}