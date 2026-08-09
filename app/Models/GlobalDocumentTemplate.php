<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobalDocumentTemplate extends Model
{
    protected $fillable = [
        'document_type',
        'content_data',
        'editor_type',
    ];
}
