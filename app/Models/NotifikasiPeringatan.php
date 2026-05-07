<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotifikasiPeringatan extends Model
{
    protected $table = 'notifikasi_peringatan';

    protected $fillable = [
        'jenis_peringatan',
        'kunci_peringatan',
        'nis',
        'siswa_id',
        'ortu_id',
        'walkel_id',
        'thread_chat_id',
        'pesan_chat_id',
        'status_kirim',
        'snapshot_data',
        'waktu_kirim',
    ];

    protected $casts = [
        'snapshot_data' => 'array',
        'waktu_kirim' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relasi ke user orang tua
    |--------------------------------------------------------------------------
    */
    public function ortu()
    {
        return $this->belongsTo(User::class, 'ortu_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Relasi ke user wali kelas
    |--------------------------------------------------------------------------
    */
    public function walkel()
    {
        return $this->belongsTo(User::class, 'walkel_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Relasi opsional ke thread chat
    |--------------------------------------------------------------------------
    */
    public function threadChat()
    {
        return $this->belongsTo(ChatThread::class, 'thread_chat_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Relasi opsional ke pesan chat
    |--------------------------------------------------------------------------
    */
    public function pesanChat()
    {
        return $this->belongsTo(ChatMessage::class, 'pesan_chat_id');
    }
}