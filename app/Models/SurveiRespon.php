<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurveiRespon extends Model
{
    use HasFactory;

    protected $table = 'survei_respon';

    protected $fillable = [
        'survei_id',
        'ortu_user_id',
        'jawaban'
    ];

    protected $casts = [
        'jawaban' => 'array',
    ];

    public function survei()
    {
        return $this->belongsTo(Survei::class, 'survei_id');
    }

    public function ortu()
    {
        return $this->belongsTo(User::class, 'ortu_user_id');
    }
    public function pertanyaan()
    {
        return $this->hasMany(SurveiPertanyaan::class);
    }

    public function respon()
    {
        return $this->hasMany(SurveiRespon::class);
    }
}
