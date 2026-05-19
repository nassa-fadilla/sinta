<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SiaClient
{
    protected string $baseUrl;
    protected string $publicUrl;
    protected string $token;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.sia.base_url'), '/');
        $this->publicUrl = rtrim((string) (config('services.sia.public_url') ?: config('services.sia.base_url')), '/');
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
                    'data' => $this->normalizePhotoUrls($json['data'] ?? []),
                    'message' => $json['message'] ?? ('HTTP ' . $res->status()),
                    'http_status' => $res->status(),
                ];
            }

            // Normalisasi agar controller SINTA tidak bingung antara "success" vs "status"
            $success = (bool) ($json['success'] ?? $json['status'] ?? true);

            $data = $this->normalizePhotoUrls($json['data'] ?? []);

            return [
                'success' => $success,
                'status' => $success,
                'data' => $data,
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
    | NORMALISASI FOTO DARI SIA
    |--------------------------------------------------------------------------
    | Bagian ini tidak mengubah struktur utama data dari SIA.
    | Fungsi ini hanya memastikan jika data memiliki field "foto", maka SINTA
    | juga memiliki "foto_url" yang bisa langsung dipakai pada Blade.
    |
    | Prioritas:
    | 1. Jika SIA sudah mengirim foto_url berupa URL lengkap, gunakan apa adanya.
    | 2. Jika foto_url masih relatif, ubah menjadi URL lengkap berdasarkan SIA_PUBLIC_URL.
    | 3. Jika foto_url belum ada tetapi field foto ada, bentuk URL dari field foto.
    */

    protected function normalizePhotoUrls($data)
    {
        if (!is_array($data)) {
            return $data;
        }

        if ($this->isListArray($data)) {
            return array_map(fn($item) => $this->normalizePhotoUrls($item), $data);
        }

        $normalized = [];

        foreach ($data as $key => $value) {
            $normalized[$key] = is_array($value)
                ? $this->normalizePhotoUrls($value)
                : $value;
        }

        $foto = $normalized['foto'] ?? null;
        $fotoUrl = $normalized['foto_url'] ?? null;

        if (!empty($fotoUrl)) {
            $normalized['foto_url'] = $this->makeAbsoluteSiaUrl((string) $fotoUrl);
        } elseif (!empty($foto)) {
            $normalized['foto_url'] = $this->resolveFotoUrl((string) $foto, $this->inferFotoFolder($normalized));
        }

        return $normalized;
    }

    protected function isListArray(array $array): bool
    {
        if ($array === []) {
            return true;
        }

        return array_keys($array) === range(0, count($array) - 1);
    }

    protected function inferFotoFolder(array $data): string
    {
        if (array_key_exists('nis', $data) || array_key_exists('nisn', $data)) {
            return 'foto_siswa';
        }

        if (array_key_exists('nuptk', $data) || array_key_exists('nip', $data)) {
            return 'foto_guru';
        }

        return 'foto_siswa';
    }

    protected function resolveFotoUrl(?string $foto, string $defaultFolder = 'foto_siswa'): ?string
    {
        if (empty($foto)) {
            return null;
        }

        $rawFoto = trim((string) $foto);

        if ($rawFoto === '') {
            return null;
        }

        if (preg_match('/^https?:\/\//i', $rawFoto)) {
            return $rawFoto;
        }

        $rawFoto = str_replace('\\', '/', $rawFoto);
        $rawFoto = preg_replace('#/+#', '/', $rawFoto);
        $rawFoto = ltrim($rawFoto, '/');

        /*
        |--------------------------------------------------------------------------
        | Jika field foto dari SIA sudah berisi path storage/...
        |--------------------------------------------------------------------------
        */
        if (str_starts_with($rawFoto, 'storage/')) {
            return $this->makeAbsoluteSiaUrl($rawFoto);
        }

        /*
        |--------------------------------------------------------------------------
        | Jika field foto dari SIA sudah berisi folder foto_siswa/foto_guru
        |--------------------------------------------------------------------------
        */
        if (
            str_starts_with($rawFoto, 'foto_siswa/') ||
            str_starts_with($rawFoto, 'foto_guru/')
        ) {
            return $this->makeAbsoluteSiaUrl('storage/' . $rawFoto);
        }

        /*
        |--------------------------------------------------------------------------
        | Jika field foto hanya berisi nama file
        |--------------------------------------------------------------------------
        */
        $basename = basename($rawFoto);

        return $this->makeAbsoluteSiaUrl('storage/' . trim($defaultFolder, '/') . '/' . $basename);
    }

    protected function makeAbsoluteSiaUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        $path = trim((string) $path);

        if ($path === '') {
            return null;
        }

        if (preg_match('/^https?:\/\//i', $path)) {
            return $path;
        }

        $path = str_replace('\\', '/', $path);
        $path = preg_replace('#/+#', '/', $path);
        $path = ltrim($path, '/');

        return "{$this->publicUrl}/{$path}";
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