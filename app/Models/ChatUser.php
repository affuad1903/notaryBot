<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatUser extends Model
{
    protected $fillable = [
        'name',
        'email',
        'session_id',
        'last_activity'
    ];

    protected $casts = [
        'last_activity' => 'datetime',
    ];
}
