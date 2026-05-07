<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
  protected $table = 'chat_messages';

  protected $fillable = [
    'thread_id',
    'direction',
    'channel',
    'sender_type',
    'sender_id',
    'message_type',
    'message_status',
    'body',
    'external_id',
    'delivered_at',
    'read_at',
  ];

  protected $casts = [
    'delivered_at' => 'datetime',
    'read_at' => 'datetime',
  ];

  /**
   * Relasi ke thread percakapan
   */
  public function thread()
  {
    return $this->belongsTo(ChatThread::class, 'thread_id');
  }

  /**
   * Relasi ke user pengirim, jika sender_id tersedia
   */
  public function sender()
  {
    return $this->belongsTo(User::class, 'sender_id');
  }
}