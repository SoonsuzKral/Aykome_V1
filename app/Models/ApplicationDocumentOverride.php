<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationDocumentOverride extends Model
{
    protected $fillable = [
        'application_id',
        'document_type',
        'content_data',
        'editor_type',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
