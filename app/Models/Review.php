<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = [
        'chat_user_id',
        'session_id',
        'rating',
        'comment'
    ];

    public function chatUser()
    {
        return $this->belongsTo(ChatUser::class);
    }
}
