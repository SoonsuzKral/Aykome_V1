<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EImzaTransaction extends Model
{
    protected $fillable = [
        'application_id',
        'pdf_type',
        'status',
        'transaction_id',
        'token',
        'orijinal_pdf',
        'imzali_pdf',
        'imzalayan_info',
        'expires_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'completed_at' => 'datetime',
            'imzalayan_info' => 'array',
        ];
    }

    protected $table = 'e_imza_transactions';

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
