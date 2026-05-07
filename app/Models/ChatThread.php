<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatThread extends Model
{
  protected $table = 'chat_threads';

  protected $fillable = [
    'owner_parent_id',
    'assigned_to_user_id',
    'status',
    'last_channel',
    'last_message_at',
  ];

  protected $casts = [
    'last_message_at' => 'datetime',
  ];

  /**
   * Pemilik thread = akun orang tua
   */
  public function ownerParent()
  {
    return $this->belongsTo(User::class, 'owner_parent_id');
  }

  /**
   * Alias agar kode lama yang memanggil owner masih tetap aman
   */
  public function owner()
  {
    return $this->belongsTo(User::class, 'owner_parent_id');
  }

  /**
   * User sekolah yang menangani thread (admin / guru)
   */
  public function assignee()
  {
    return $this->belongsTo(User::class, 'assigned_to_user_id');
  }

  /**
   * Semua pesan dalam thread ini
   */
  public function messages()
  {
    return $this->hasMany(ChatMessage::class, 'thread_id')
      ->orderBy('created_at', 'asc');
  }

  /**
   * Pesan terakhir dalam thread
   */
  public function lastMessage()
  {
    return $this->hasOne(ChatMessage::class, 'thread_id')->latestOfMany();
  }
}