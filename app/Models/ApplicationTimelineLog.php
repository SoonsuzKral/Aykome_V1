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
}
