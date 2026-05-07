<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\SiaClient;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class GuruUserSeeder extends Seeder
{
    protected SiaClient $sia;

    public function __construct()
    {
        $this->sia = app(SiaClient::class);
    }

    public function run(): void
    {
        $nuptkKepsek = $this->resolveNuptkKepsek();

        $countKepsek = 0;
        $countWalkel = 0;
        $countSkip = 0;

        /*
        |--------------------------------------------------------------------------
        | 1. Sinkronisasi akun Kepala Sekolah
        |--------------------------------------------------------------------------
        */
        if ($nuptkKepsek !== '') {
            $kepsek = $this->findGuruByKey($nuptkKepsek);

            if ($kepsek) {
                $user = $this->createOrUpdateGuruUser($kepsek, 'kepsek');

                if ($user) {
                    $countKepsek++;
                } else {
                    $countSkip++;
                }
            } else {
                $countSkip++;
                $this->command?->warn("Data kepala sekolah dengan NUPTK {$nuptkKepsek} tidak ditemukan dari API SIA.");
            }
        } else {
            $countSkip++;
            $this->command?->warn('NUPTK kepala sekolah belum diatur pada KEPSEK_NUPTK di .env.');
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Sinkronisasi akun Wali Kelas dari data rombel aktif
        |--------------------------------------------------------------------------
        */
        $rombelRows = $this->getRombelAktif();

        foreach ($rombelRows as $rombel) {
            $rombelDetail = $this->getRombelDetail($rombel);

            if (!$rombelDetail) {
                $countSkip++;
                continue;
            }

            $guru = $this->extractWaliKelasFromRombel($rombelDetail);

            if (!$guru) {
                $countSkip++;
                continue;
            }

            $identifier = $this->resolveGuruIdentifier($guru);

            if ($identifier === '') {
                $countSkip++;
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Jika guru tersebut adalah kepala sekolah, jangan diturunkan menjadi walkel.
            |--------------------------------------------------------------------------
            */
            if ($nuptkKepsek !== '' && $identifier === $nuptkKepsek) {
                $countSkip++;
                continue;
            }

            $user = $this->createOrUpdateGuruUser($guru, 'walkel');

            if ($user) {
                $countWalkel++;
            } else {
                $countSkip++;
            }
        }

        $this->command?->info('Sinkronisasi selesai.');
        $this->command?->info("Kepala sekolah dibuat/diperbarui: {$countKepsek}");
        $this->command?->info("Wali kelas dibuat/diperbarui: {$countWalkel}");
        $this->command?->info("Data dilewati: {$countSkip}");
    }

    /*
    |--------------------------------------------------------------------------
    | Konfigurasi NUPTK Kepala Sekolah
    |--------------------------------------------------------------------------
    */

    protected function resolveNuptkKepsek(): string
    {
        return trim((string) (
            config('services.sia.kepsek_nuptk')
            ?: env('KEPSEK_NUPTK')
            ?: ''
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | Ambil Data Guru
    |--------------------------------------------------------------------------
    */

    protected function findGuruByKey(string $key): ?array
    {
        $key = trim($key);

        if ($key === '') {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Prioritas pertama: endpoint guru by key.
        | Biasanya key dapat berupa NUPTK, NIP, atau ID guru sesuai API SIA.
        |--------------------------------------------------------------------------
        */
        $response = $this->sia->getGuruByKey($key);
        $data = $this->extractSingleData($response);

        if ($data) {
            return $data;
        }

        /*
        |--------------------------------------------------------------------------
        | Fallback: cari melalui daftar master guru.
        |--------------------------------------------------------------------------
        */
        $response = $this->sia->masterGuru($key);
        $rows = $this->toList($response['data'] ?? []);

        foreach ($rows as $row) {
            $nuptk = trim((string) ($row['nuptk'] ?? ''));
            $nip = trim((string) ($row['nip'] ?? ''));
            $id = trim((string) ($row['id'] ?? ''));

            if ($key === $nuptk || $key === $nip || $key === $id) {
                return $row;
            }
        }

        return $rows[0] ?? null;
    }

    protected function findGuruById($id): ?array
    {
        $id = trim((string) $id);

        if ($id === '') {
            return null;
        }

        $response = $this->sia->masterGuruDetail($id);
        $data = $this->extractSingleData($response);

        if ($data) {
            return $data;
        }

        return $this->findGuruByKey($id);
    }

    /*
    |--------------------------------------------------------------------------
    | Ambil Data Rombel
    |--------------------------------------------------------------------------
    */

    protected function getRombelAktif(): array
    {
        /*
        |--------------------------------------------------------------------------
        | Prioritas pertama: rombel aktif dari API SIA.
        |--------------------------------------------------------------------------
        */
        $response = $this->sia->masterRombel(null, ['aktif' => 1]);
        $rows = $this->toList($response['data'] ?? []);

        if (!empty($rows)) {
            return $rows;
        }

        /*
        |--------------------------------------------------------------------------
        | Fallback jika API tidak menerima filter aktif.
        |--------------------------------------------------------------------------
        */
        $response = $this->sia->masterRombel();
        return $this->toList($response['data'] ?? []);
    }

    protected function getRombelDetail(array $rombel): ?array
    {
        $id = $rombel['id'] ?? null;

        if (!$id) {
            return $rombel;
        }

        $response = $this->sia->masterRombelDetail($id);
        $data = $this->extractSingleData($response);

        return $data ?: $rombel;
    }

    protected function extractWaliKelasFromRombel(array $rombel): ?array
    {
        /*
        |--------------------------------------------------------------------------
        | Kemungkinan respons API memuat objek guru wali kelas.
        |--------------------------------------------------------------------------
        */
        foreach (['wali_kelas', 'walikelas', 'guru_wali', 'wali', 'guru'] as $key) {
            if (isset($rombel[$key]) && is_array($rombel[$key])) {
                $guru = $this->normalizeGuruRow($rombel[$key]);

                if ($guru) {
                    return $guru;
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Kemungkinan respons API hanya menyimpan ID guru wali kelas.
        |--------------------------------------------------------------------------
        */
        foreach (['wali_kelas_id', 'walikelas_id', 'guru_id', 'id_guru', 'wali_id'] as $key) {
            if (!empty($rombel[$key])) {
                $guru = $this->findGuruById($rombel[$key]);

                if ($guru) {
                    return $guru;
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Jika data rombel langsung memuat atribut guru pada level utama.
        |--------------------------------------------------------------------------
        */
        $directGuru = $this->normalizeGuruRow($rombel);

        if ($directGuru && $this->resolveGuruIdentifier($directGuru) !== '') {
            return $directGuru;
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | Buat / Update User Lokal SINTA
    |--------------------------------------------------------------------------
    */

    protected function createOrUpdateGuruUser(array $guru, string $role): ?User
    {
        $identifier = $this->resolveGuruIdentifier($guru);

        if ($identifier === '') {
            return null;
        }

        $name = trim((string) (
            $guru['nama']
            ?? $guru['name']
            ?? $guru['nama_guru']
            ?? 'Pengguna SINTA'
        ));

        $email = trim((string) ($guru['email'] ?? ''));

        if ($email === '') {
            $email = "{$identifier}@sinta.local";
        }

        $passwordPlain = $this->resolvePasswordFromTanggalLahir($guru);

        $user = User::query()
            ->where('sia_user_id', $identifier)
            ->orWhere('email', $email)
            ->first();

        if (!$user) {
            return User::create([
                'sia_user_id' => $identifier,
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($passwordPlain),
                'role' => $role,
                'remember_token' => Str::random(10),
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Jangan ubah akun admin.
        | Jika user sudah kepsek, jangan diturunkan menjadi walkel.
        |--------------------------------------------------------------------------
        */
        if ($user->role === 'admin') {
            return $user;
        }

        $finalRole = $role;

        if ($user->role === 'kepsek' && $role === 'walkel') {
            $finalRole = 'kepsek';
        }

        $user->update([
            'sia_user_id' => $identifier,
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($passwordPlain),
            'role' => $finalRole,
            'remember_token' => $user->remember_token ?: Str::random(10),
        ]);

        return $user;
    }

    protected function resolveGuruIdentifier(array $guru): string
    {
        return trim((string) (
            $guru['nuptk']
            ?? $guru['nip']
            ?? $guru['id']
            ?? $guru['guru_id']
            ?? ''
        ));
    }

    protected function resolvePasswordFromTanggalLahir(array $guru): string
    {
        $tanggalLahir = $guru['tanggal_lahir']
            ?? $guru['tgl_lahir']
            ?? $guru['birth_date']
            ?? null;

        if (!$tanggalLahir) {
            return '12345678';
        }

        try {
            return Carbon::parse($tanggalLahir)->format('Y-m-d');
        } catch (\Throwable $e) {
            return '12345678';
        }
    }

    protected function normalizeGuruRow(array $row): ?array
    {
        $identifier = trim((string) (
            $row['nuptk']
            ?? $row['nip']
            ?? $row['id']
            ?? $row['guru_id']
            ?? ''
        ));

        if ($identifier === '') {
            return null;
        }

        return $row;
    }

    /*
    |--------------------------------------------------------------------------
    | Normalisasi Respons API
    |--------------------------------------------------------------------------
    */

    protected function extractSingleData(array $response): ?array
    {
        if (($response['status'] ?? false) !== true && ($response['success'] ?? false) !== true) {
            return null;
        }

        $data = $response['data'] ?? null;

        if (!$data) {
            return null;
        }

        if (is_array($data)) {
            /*
            |--------------------------------------------------------------------------
            | Jika response langsung object associative.
            |--------------------------------------------------------------------------
            */
            if ($this->isAssoc($data)) {
                return $data;
            }

            /*
            |--------------------------------------------------------------------------
            | Jika response berupa list, ambil data pertama.
            |--------------------------------------------------------------------------
            */
            return isset($data[0]) && is_array($data[0]) ? $data[0] : null;
        }

        if (is_object($data)) {
            return (array) $data;
        }

        return null;
    }

    protected function toList($value): array
    {
        if (is_array($value)) {
            if ($this->isAssoc($value)) {
                return [$this->toArray($value)];
            }

            return array_values(array_map(fn($item) => $this->toArray($item), $value));
        }

        if (is_object($value)) {
            return [$this->toArray($value)];
        }

        return [];
    }

    protected function toArray($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_object($value)) {
            return (array) $value;
        }

        return [];
    }

    protected function isAssoc(array $array): bool
    {
        if ($array === []) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }
}