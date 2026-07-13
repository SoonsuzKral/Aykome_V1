<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FieldTaskMedia extends Model
{
    protected $table = 'field_task_media';

    protected $fillable = [
        'field_task_id',
        'step',
        'image_path',
        'caption',
    ];

    public function fieldTask(): BelongsTo
    {
        return $this->belongsTo(FieldTask::class);
    }
}
