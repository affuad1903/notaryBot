<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnansweredQuestion extends Model
{
    protected $fillable = [
        'chat_user_id',
        'session_id',
        'question',
        'bot_response'
    ];

    public function chatUser()
    {
        return $this->belongsTo(ChatUser::class);
    }
}
