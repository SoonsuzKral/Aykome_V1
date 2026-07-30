<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EImzaIstemci extends Model
{
    protected $fillable = [
        'api_key',
        'label',
        'son_erisim',
    ];

    protected $table = 'e_imza_istemcileri';
}
