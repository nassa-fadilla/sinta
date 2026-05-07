<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurveiOpsi extends Model
{
    use HasFactory;
    protected $table = 'survei_opsi';
    protected $fillable = ['pertanyaan_id', 'opsi'];

    public function pertanyaan()
    {
        return $this->belongsTo(SurveiPertanyaan::class, 'pertanyaan_id');
    }
}