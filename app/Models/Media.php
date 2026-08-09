<?php

namespace App\Models;

use Spatie\MediaLibrary\MediaCollections\Models\Media as SpatieMedia;
use Illuminate\Support\Facades\DB;

class Media extends SpatieMedia
{
    public $incrementing = true;

    public $keyType = 'int';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($media) {
            if (DB::connection()->getDriverName() === 'oracle') {
                $maxId = DB::connection()->selectOne("SELECT NVL(MAX(ID), 0) + 1 AS NEXT_ID FROM AYKOME_USER.MEDIA");
                $media->id = $maxId->next_id;
                $media->setIncrementing(false);
            }
        });
    }
}
