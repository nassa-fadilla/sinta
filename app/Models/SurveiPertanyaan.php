<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurveiPertanyaan extends Model
{
    use HasFactory;

    protected $table = 'survei_pertanyaan';

    protected $fillable = [
        'survei_id',
        'pertanyaan',
        'tipe',
        'pilihan',
        'urutan'
    ];

    protected $casts = [
        'pilihan' => 'array',
    ];

    public function opsi()
    {
        return $this->hasMany(SurveiOpsi::class, 'pertanyaan_id');
    }

    public function survei()
    {
        return $this->belongsTo(Survei::class, 'survei_id');
    }
}
