<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Survei extends Model
{
    use HasFactory;

    protected $table = 'survei';

    protected $fillable = [
        'judul',
        'deskripsi',
        'mulai_at',
        'akhir_at',
        'is_active',
        'created_by'
    ];

    public function pertanyaan()
    {
        return $this->hasMany(SurveiPertanyaan::class, 'survei_id');
    }

    public function respon()
    {
        return $this->hasMany(SurveiRespon::class, 'survei_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
