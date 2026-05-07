<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SiaClient
{
    protected string $baseUrl;
    protected string $token;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.sia.base_url'), '/');
        $this->token = (string) config('services.sia.token');
    }

    protected function client()
    {
        return Http::withHeaders([
            'X-SINTA-TOKEN' => $this->token,
            'Accept' => 'application/json',
        ])->timeout(20);
    }

    protected function safeGet(string $path, array $query = []): array
    {
        try {
            $res = $this->client()->get("{$this->baseUrl}{$path}", array_filter(
                $query,
                fn($v) => !is_null($v) && $v !== ''
            ));

            $json = $res->json();

            if (!is_array($json)) {
                return [
                    'success' => false,
                    'status' => false,
                    'data' => [],
                    'message' => 'Response SIA tidak valid',
                ];
            }

            if (!$res->successful()) {
                return [
                    'success' => false,
                    'status' => false,
                    'data' => $json['data'] ?? [],
                    'message' => $json['message'] ?? ('HTTP ' . $res->status()),
                    'http_status' => $res->status(),
                ];
            }

            // Normalisasi agar controller SINTA tidak bingung antara "success" vs "status"
            $success = (bool) ($json['success'] ?? $json['status'] ?? true);

            return [
                'success' => $success,
                'status' => $success,
                'data' => $json['data'] ?? [],
                'message' => $json['message'] ?? null,
                'meta' => $json['meta'] ?? null,
                'pagination' => $json['pagination'] ?? null,
                'raw' => $json,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'status' => false,
                'data' => [],
                'message' => $e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */

    public function dashboardSummary(): array
    {
        return $this->safeGet('/api/sinta/dashboard/summary');
    }

    public function dashboardPresensiToday(): array
    {
        return $this->safeGet('/api/sinta/dashboard/presensi-today');
    }

    public function dashboardPresensiTrend7(): array
    {
        return $this->safeGet('/api/sinta/dashboard/presensi-trend7');
    }

    public function dashboardPresensiTrendMonthly(): array
    {
        return $this->safeGet('/api/sinta/dashboard/presensi-trend-monthly');
    }

    public function dashboardRekapNilaiPerKelas(): array
    {
        return $this->safeGet('/api/sinta/dashboard/rekap-nilai-kelas');
    }

    public function dashboardRekapNilaiPerTingkat(): array
    {
        return $this->safeGet('/api/sinta/dashboard/rekap-nilai-tingkat');
    }

    public function dashboardRekapNilaiKelas(): array
    {
        return $this->dashboardRekapNilaiPerKelas();
    }

    public function dashboardRataNilaiGlobal(): array
    {
        return $this->safeGet('/api/sinta/dashboard/rata-nilai-global');
    }

    public function dashboardTopSiswa(): array
    {
        return $this->safeGet('/api/sinta/dashboard/top-siswa');
    }

    /*
    |--------------------------------------------------------------------------
    | MASTER SISWA
    |--------------------------------------------------------------------------
    */

    public function masterSiswa($q = null, array $filters = []): array
    {
        return $this->safeGet('/api/sinta/master/siswa', array_merge(['q' => $q], $filters));
    }

    public function masterSiswaDetail($id): array
    {
        return $this->safeGet("/api/sinta/master/siswa/{$id}");
    }

    public function masterSiswaRombelAktif($id): array
    {
        return $this->safeGet("/api/sinta/master/siswa/{$id}/rombel-aktif");
    }

    /*
    |--------------------------------------------------------------------------
    | MASTER GURU
    |--------------------------------------------------------------------------
    */

    public function masterGuru($q = null, array $filters = []): array
    {
        return $this->safeGet('/api/sinta/master/guru', array_merge(['q' => $q], $filters));
    }

    public function masterGuruDetail($id): array
    {
        return $this->safeGet("/api/sinta/master/guru/{$id}");
    }

    /*
    |--------------------------------------------------------------------------
    | GURU API
    |--------------------------------------------------------------------------
    */

    public function getGuru($q = null): array
    {
        return $this->safeGet('/api/sinta/guru', ['q' => $q]);
    }

    public function getGuruByKey($key): array
    {
        return $this->safeGet("/api/sinta/guru/{$key}");
    }

    public function getGuruByNuptk($nuptk): array
    {
        return $this->getGuruByKey($nuptk);
    }

    public function getGuruByNip($nip): array
    {
        return $this->getGuruByKey($nip);
    }

    /*
    |--------------------------------------------------------------------------
    | MASTER MAPEL
    |--------------------------------------------------------------------------
    */

    public function masterMapel(array $filters = []): array
    {
        return $this->safeGet('/api/sinta/master/mapel', $filters);
    }

    public function masterMapelDetail($id): array
    {
        return $this->safeGet("/api/sinta/master/mapel/{$id}");
    }

    /*
    |--------------------------------------------------------------------------
    | MASTER JADWAL
    |--------------------------------------------------------------------------
    */

    public function masterJadwal(array $filters = []): array
    {
        return $this->safeGet('/api/sinta/master/jadwal', $filters);
    }

    public function masterJadwalDetail($id): array
    {
        return $this->safeGet("/api/sinta/master/jadwal/{$id}");
    }

    /*
    |--------------------------------------------------------------------------
    | JADWAL SISWA / ROMBEL
    |--------------------------------------------------------------------------
    */

    public function getJadwalRombel(int $rombelId, $hari = null): array
    {
        $filters = [
            'rombel' => $rombelId,
            'hari' => $hari,
        ];

        return $this->safeGet('/api/sinta/jadwal', $filters);
    }

    public function getJadwalRombelHariIni(int $rombelId, ?\DateTimeInterface $tanggal = null): array
    {
        $tanggal = $tanggal ?: now('Asia/Jakarta');
        $hariNama = $tanggal->locale('id')->dayName;

        return $this->getJadwalRombel($rombelId, $hariNama);
    }

    /*
    |--------------------------------------------------------------------------
    | MASTER ROMBEL
    |--------------------------------------------------------------------------
    */

    public function masterRombel($q = null, array $filters = []): array
    {
        return $this->safeGet('/api/sinta/master/rombel', array_merge(['q' => $q], $filters));
    }

    public function masterRombelDetail($id): array
    {
        return $this->safeGet("/api/sinta/master/rombel/{$id}");
    }

    public function masterRombelAnggota($id): array
    {
        return $this->safeGet("/api/sinta/master/rombel/{$id}/anggota");
    }

    public function masterRombelJadwal($id, array $filters = []): array
    {
        return $this->safeGet("/api/sinta/master/rombel/{$id}/jadwal", $filters);
    }

    public function masterRombelSesiPresensi($rombelId): array
    {
        return $this->safeGet("/api/sinta/master/rombel/{$rombelId}/sesi-presensi");
    }

    /*
    |--------------------------------------------------------------------------
    | MASTER TAHUN AJARAN
    |--------------------------------------------------------------------------
    */

    public function masterTahunAjaran($q = null, array $filters = []): array
    {
        return $this->safeGet('/api/sinta/master/tahun-ajaran', array_merge(['q' => $q], $filters));
    }

    public function masterTahunAjaranDetail($id): array
    {
        return $this->safeGet("/api/sinta/master/tahun-ajaran/{$id}");
    }

    public function masterTahunAjaranAktif(): array
    {
        return $this->safeGet('/api/sinta/master/tahun-ajaran-aktif');
    }

    /*
    |--------------------------------------------------------------------------
    | SISWA API (PORTAL ORTU / NILAI / PRESENSI)
    |--------------------------------------------------------------------------
    */

    public function getSiswaByNis($nis): array
    {
        return $this->safeGet("/api/sinta/siswa/{$nis}");
    }

    public function getNilaiByNis($nis, array $filters = []): array
    {
        return $this->safeGet("/api/sinta/siswa/{$nis}/nilai", $filters);
    }

    public function getPresensiByNis($nis, array $filters = []): array
    {
        return $this->safeGet("/api/sinta/siswa/{$nis}/presensi", $filters);
    }

    public function getEkskulByNis($nis, array $filters = []): array
    {
        return $this->safeGet('/api/sinta/master/ekskul', array_merge($filters, [
            'nis' => $nis,
        ]));
    }

    /*
    |--------------------------------------------------------------------------
    | PRESENSI
    |--------------------------------------------------------------------------
    */

    public function getPresensiSesi($sesiId): array
    {
        return $this->safeGet("/api/sinta/presensi/sesi/{$sesiId}");
    }

    public function masterRombelPresensi($rombelId, array $filters = []): array
    {
        return $this->safeGet("/api/sinta/presensi/rombel/{$rombelId}", $filters);
    }

    /*
    |--------------------------------------------------------------------------
    | MASTER EKSKUL
    |--------------------------------------------------------------------------
    */

    public function masterEkskul(array $filters = []): array
    {
        return $this->safeGet('/api/sinta/master/ekskul', $filters);
    }

    public function masterEkskulDetail($id): array
    {
        return $this->safeGet("/api/sinta/master/ekskul/{$id}");
    }
}