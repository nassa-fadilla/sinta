<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pengumuman extends Model
{
    use HasFactory;

    protected $table = 'pengumuman';

    protected $fillable = [
        'judul',
        'isi',
        'jenis',
        'target_scope',
        'target_tingkat',
        'publish_at',
        'expire_at',
        'is_active',
        'status',
        'reject_note',
        'approved_at',
        'approved_by',
        'pdf_path',
        'tahun_ajaran_id',
        'created_by',
    ];

    protected $casts = [
        'publish_at' => 'datetime',
        'expire_at' => 'datetime',
        'approved_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELASI LOKAL SINTA
    |--------------------------------------------------------------------------
    */

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPE
    |--------------------------------------------------------------------------
    */

    public function scopeAktif($query)
    {
        $now = now();

        return $query
            ->where('status', 'approved')
            ->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('publish_at')
                    ->orWhere('publish_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('expire_at')
                    ->orWhere('expire_at', '>=', $now);
            });
    }

    /*
    |--------------------------------------------------------------------------
    | TARGET HELPER
    |--------------------------------------------------------------------------
    | Pengumuman adalah data lokal SINTA, tetapi tingkat siswa/guru/rombel
    | harus tetap berasal dari API SIA. Helper ini hanya mencocokkan target
    | pengumuman dengan tingkat rombel yang sudah diambil dari SIA.
    */

    public static function normalizeTingkat($value): ?string
    {
        if (is_array($value)) {
            return self::normalizeTingkat(
                $value['nama_rombel'] ?? null
                ?? $value['nama'] ?? null
                ?? $value['label'] ?? null
                ?? $value['tingkat'] ?? null
            );
        }

        if (is_object($value)) {
            return self::normalizeTingkat((array) $value);
        }

        $text = strtoupper(trim((string) $value));

        if ($text === '' || $text === '-') {
            return null;
        }

        $text = str_replace(['-', '_', '.', '/', '\\'], ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        $compact = preg_replace('/[^A-Z0-9]/', '', $text);

        /*
        |--------------------------------------------------------------------------
        | Urutan wajib dari yang paling panjang
        |--------------------------------------------------------------------------
        | XII harus dicek sebelum XI, dan XI harus dicek sebelum X.
        | Jika X dicek dulu, rombel XI/XII bisa salah terbaca sebagai X.
        */

        if (
            str_starts_with($compact, 'XII') ||
            $compact === '12' ||
            preg_match('/(^|[^0-9])12([^0-9]|$)/', $text)
        ) {
            return 'XII';
        }

        if (
            str_starts_with($compact, 'XI') ||
            $compact === '11' ||
            preg_match('/(^|[^0-9])11([^0-9]|$)/', $text)
        ) {
            return 'XI';
        }

        if (
            str_starts_with($compact, 'X') ||
            $compact === '10' ||
            preg_match('/(^|[^0-9])10([^0-9]|$)/', $text)
        ) {
            return 'X';
        }

        return null;
    }

    public static function normalizeTargetScope($value): string
    {
        $scope = strtolower(trim((string) $value));

        return match ($scope) {
            '', 'semua', 'all', 'umum' => 'all',
            'tingkat', 'level' => 'tingkat',
            default => $scope,
        };
    }

    public function isUntukSemua(): bool
    {
        return self::normalizeTargetScope($this->target_scope) === 'all';
    }

    public function isUntukTingkat(?string $tingkatSia): bool
    {
        $scope = self::normalizeTargetScope($this->target_scope);

        if ($scope === 'all') {
            return true;
        }

        if ($scope !== 'tingkat') {
            return false;
        }

        $tingkatPengumuman = self::normalizeTingkat($this->target_tingkat);
        $tingkatRombelSia = self::normalizeTingkat($tingkatSia);

        if ($tingkatPengumuman === null || $tingkatRombelSia === null) {
            return false;
        }

        return $tingkatPengumuman === $tingkatRombelSia;
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSOR HELPER
    |--------------------------------------------------------------------------
    */

    public function getTargetLabelAttribute(): string
    {
        $scope = self::normalizeTargetScope($this->target_scope);

        return match ($scope) {
            'all' => 'Semua',
            'tingkat' => 'Tingkat ' . (self::normalizeTingkat($this->target_tingkat) ?? '-'),
            default => 'Tidak ditentukan',
        };
    }

    public function getTargetTingkatNormalizedAttribute(): ?string
    {
        return self::normalizeTingkat($this->target_tingkat);
    }
}