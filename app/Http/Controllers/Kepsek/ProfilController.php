<?php

namespace App\Http\Controllers\Kepsek;

use App\Http\Controllers\Controller;
use App\Services\SiaClient;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ProfilController extends Controller
{
    protected SiaClient $sia;

    public function __construct(SiaClient $sia)
    {
        $this->sia = $sia;
    }

    public function show()
    {
        $user = Auth::user();
        $guruApi = $this->resolveKepsekGuru($user);

        return view('kepsek.profil.show', [
            'user' => $user,
            'guruApi' => $guruApi,
        ]);
    }

    public function photo()
    {
        $user = Auth::user();
        $guruApi = $this->resolveKepsekGuru($user);

        $defaultFotoPath = $this->defaultPhotoPath();

        if (!is_array($guruApi) || empty($guruApi)) {
            return response()->file($defaultFotoPath, [
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Prioritas utama: foto dari URL publik SIA
        |--------------------------------------------------------------------------
        | Jika API SIA sudah mengirim foto_url, photo_url, atau field foto yang
        | dapat dibentuk menjadi URL storage SIA, maka gambar diambil dari sana.
        */
        $fotoUrl = $this->resolveGuruPhotoUrl($guruApi);

        if ($fotoUrl) {
            try {
                $res = Http::timeout(20)
                    ->withoutVerifying()
                    ->get($fotoUrl);

                if ($res->successful() && !empty($res->body())) {
                    $contentType = $res->header('Content-Type', 'image/jpeg');

                    if (str_contains(strtolower($contentType), 'image')) {
                        return response($res->body(), 200)
                            ->header('Content-Type', $contentType)
                            ->header('Cache-Control', 'public, max-age=3600');
                    }
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Fallback: file lokal SINTA
        |--------------------------------------------------------------------------
        | Tetap dipertahankan agar fungsi lama tidak rusak jika sebelumnya foto
        | pernah tersedia di folder lokal public/sia/foto_guru atau storage lokal.
        */
        $localPath = $this->resolveGuruPhotoLocalPath($guruApi);

        if ($localPath && is_file($localPath)) {
            return response()->file($localPath, [
                'Content-Type' => $this->guessMimeType($localPath),
                'Cache-Control' => 'public, max-age=3600',
            ]);
        }

        return response()->file($defaultFotoPath, [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    private function resolveKepsekGuru($user): ?array
    {
        $lookupKey = null;

        if (!empty($user?->sia_user_id)) {
            $lookupKey = trim((string) $user->sia_user_id);
        } elseif (!empty(config('services.sia.kepsek_nuptk'))) {
            $lookupKey = trim((string) config('services.sia.kepsek_nuptk'));
        } elseif (!empty(env('KEPSEK_NUPTK'))) {
            $lookupKey = trim((string) env('KEPSEK_NUPTK'));
        }

        try {
            if ($lookupKey !== null && $lookupKey !== '') {
                $resp = $this->sia->getGuruByKey($lookupKey);

                if ($this->responseOk($resp) && !empty($resp['data']) && is_array($resp['data'])) {
                    return $this->normalizeGuruProfile($resp['data']);
                }
            }

            if (!empty($user?->email)) {
                $resp = $this->sia->getGuru($user->email);

                if (
                    is_array($resp) &&
                    !empty($resp['data']) &&
                    is_array($resp['data'])
                ) {
                    foreach ($resp['data'] as $row) {
                        if (
                            is_array($row) &&
                            strtolower((string) ($row['email'] ?? '')) === strtolower((string) $user->email)
                        ) {
                            return $this->normalizeGuruProfile($row);
                        }
                    }

                    if (!empty($resp['data'][0]) && is_array($resp['data'][0])) {
                        return $this->normalizeGuruProfile($resp['data'][0]);
                    }
                }
            }

            if (!empty($user?->name)) {
                $resp = $this->sia->getGuru($user->name);

                if (
                    is_array($resp) &&
                    !empty($resp['data']) &&
                    is_array($resp['data'])
                ) {
                    foreach ($resp['data'] as $row) {
                        if (
                            is_array($row) &&
                            $this->normalizeName($row['nama'] ?? '') === $this->normalizeName($user->name)
                        ) {
                            return $this->normalizeGuruProfile($row);
                        }
                    }

                    if (!empty($resp['data'][0]) && is_array($resp['data'][0])) {
                        return $this->normalizeGuruProfile($resp['data'][0]);
                    }
                }
            }
        } catch (\Throwable $e) {
            report($e);
            return null;
        }

        return null;
    }

    private function normalizeGuruProfile(array $guru): array
    {
        /*
        |--------------------------------------------------------------------------
        | Normalisasi foto untuk view
        |--------------------------------------------------------------------------
        | Field asli dari API SIA tetap dipertahankan. Bagian ini hanya memastikan
        | foto_url tersedia dalam bentuk URL lengkap jika memungkinkan.
        */
        $fotoUrl = $this->resolveGuruPhotoUrl($guru);

        if ($fotoUrl) {
            $guru['foto_url'] = $fotoUrl;
        }

        return $guru;
    }

    private function resolveGuruPhotoUrl(array $guru): ?string
    {
        /*
        |--------------------------------------------------------------------------
        | Prioritas 1: URL eksplisit dari API SIA
        |--------------------------------------------------------------------------
        */
        $fotoUrl = $this->pickString(
            $guru['foto_url'] ?? null,
            $guru['photo_url'] ?? null,
            $guru['url_foto'] ?? null,
            $guru['avatar_url'] ?? null,
            data_get($guru, 'profil.foto_url'),
            data_get($guru, 'detail.foto_url'),
            data_get($guru, 'profil.photo_url'),
            data_get($guru, 'detail.photo_url')
        );

        if ($fotoUrl) {
            $fotoUrl = trim((string) $fotoUrl);

            if (filter_var($fotoUrl, FILTER_VALIDATE_URL)) {
                return $fotoUrl;
            }

            $fotoUrl = $this->normalizeRelativePhotoPath($fotoUrl);
            $publicUrl = $this->siaPublicUrl();

            if ($publicUrl !== '') {
                return $publicUrl . '/' . $fotoUrl;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Prioritas 2: field path/nama file foto
        |--------------------------------------------------------------------------
        */
        $foto = $this->pickString(
            $guru['foto'] ?? null,
            $guru['photo'] ?? null,
            $guru['foto_guru'] ?? null,
            $guru['foto_path'] ?? null,
            $guru['path_foto'] ?? null,
            $guru['avatar'] ?? null,
            $guru['gambar'] ?? null,
            $guru['image'] ?? null,
            data_get($guru, 'profil.foto'),
            data_get($guru, 'detail.foto'),
            data_get($guru, 'profil.photo'),
            data_get($guru, 'detail.photo')
        );

        if (!$foto) {
            return null;
        }

        $foto = trim((string) $foto);

        if ($foto === '' || $foto === '-') {
            return null;
        }

        if (filter_var($foto, FILTER_VALIDATE_URL)) {
            return $foto;
        }

        $foto = $this->normalizeRelativePhotoPath($foto);

        if ($foto === '') {
            return null;
        }

        $publicUrl = $this->siaPublicUrl();

        if ($publicUrl === '') {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Jika field foto sudah berbentuk public/storage/...
        |--------------------------------------------------------------------------
        */
        if (Str::startsWith($foto, 'public/storage/')) {
            $foto = preg_replace('#^public/#', '', $foto);
            return $publicUrl . '/' . $foto;
        }

        /*
        |--------------------------------------------------------------------------
        | Jika field foto sudah berbentuk storage/...
        |--------------------------------------------------------------------------
        */
        if (Str::startsWith($foto, 'storage/')) {
            return $publicUrl . '/' . $foto;
        }

        /*
        |--------------------------------------------------------------------------
        | Jika field foto sudah berbentuk foto_guru/...
        |--------------------------------------------------------------------------
        */
        if (Str::startsWith($foto, 'foto_guru/')) {
            return $publicUrl . '/storage/' . $foto;
        }

        /*
        |--------------------------------------------------------------------------
        | Jika field foto berisi uploads/... atau guru/...
        |--------------------------------------------------------------------------
        */
        if (Str::startsWith($foto, ['uploads/', 'guru/'])) {
            return $publicUrl . '/' . $foto;
        }

        /*
        |--------------------------------------------------------------------------
        | Jika field foto hanya nama file
        |--------------------------------------------------------------------------
        */
        $basename = basename($foto);

        return $publicUrl . '/storage/foto_guru/' . $basename;
    }

    private function resolveGuruPhotoLocalPath(array $guru): ?string
    {
        $fotoRaw = $this->pickString(
            $guru['foto'] ?? null,
            $guru['photo'] ?? null,
            $guru['foto_guru'] ?? null,
            $guru['foto_path'] ?? null,
            $guru['path_foto'] ?? null,
            $guru['avatar'] ?? null,
            $guru['gambar'] ?? null,
            $guru['image'] ?? null,
            data_get($guru, 'profil.foto'),
            data_get($guru, 'detail.foto'),
            data_get($guru, 'profil.photo'),
            data_get($guru, 'detail.photo')
        );

        if (!$fotoRaw) {
            return null;
        }

        $foto = trim((string) $fotoRaw);

        if ($foto === '' || $foto === '-') {
            return null;
        }

        if (filter_var($foto, FILTER_VALIDATE_URL)) {
            $pathFromUrl = parse_url($foto, PHP_URL_PATH);
            $basename = basename((string) $pathFromUrl);
        } else {
            $foto = $this->normalizeRelativePhotoPath($foto);
            $basename = basename($foto);
        }

        $candidates = [];

        if (!empty($foto) && !filter_var($foto, FILTER_VALIDATE_URL)) {
            $candidates[] = public_path($foto);
            $candidates[] = public_path('storage/' . $foto);
            $candidates[] = public_path('sia/' . $foto);

            if (Str::startsWith($foto, 'public/storage/')) {
                $withoutPublic = preg_replace('#^public/#', '', $foto);
                $candidates[] = public_path($withoutPublic);
            }
        }

        if ($basename && $basename !== '.' && $basename !== '/') {
            $candidates[] = public_path('sia/foto_guru/' . $basename);
            $candidates[] = public_path('foto_guru/' . $basename);
            $candidates[] = public_path('storage/foto_guru/' . $basename);
            $candidates[] = public_path('storage/guru/' . $basename);
            $candidates[] = public_path('images/foto_guru/' . $basename);
            $candidates[] = public_path('images/guru/' . $basename);
        }

        /*
        |--------------------------------------------------------------------------
        | Tambahan pencarian berdasarkan identitas guru
        |--------------------------------------------------------------------------
        */
        $identityKeys = array_filter([
            $this->pickString($guru['nuptk'] ?? null),
            $this->pickString($guru['nip'] ?? null),
            $this->pickString($guru['id'] ?? null),
        ]);

        $extensions = ['jpg', 'jpeg', 'png', 'webp'];

        foreach ($identityKeys as $key) {
            $safeKey = trim((string) $key);

            if ($safeKey === '') {
                continue;
            }

            foreach ($extensions as $ext) {
                $candidates[] = public_path('sia/foto_guru/' . $safeKey . '.' . $ext);
                $candidates[] = public_path('foto_guru/' . $safeKey . '.' . $ext);
                $candidates[] = public_path('storage/foto_guru/' . $safeKey . '.' . $ext);
                $candidates[] = public_path('images/foto_guru/' . $safeKey . '.' . $ext);
            }
        }

        foreach (array_unique(array_filter($candidates)) as $path) {
            if ($path && is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    private function normalizeRelativePhotoPath(string $path): string
    {
        $path = trim($path);
        $path = str_replace('\\', '/', $path);
        $path = preg_replace('#/+#', '/', $path);
        $path = ltrim($path, '/');

        return $path;
    }

    private function siaPublicUrl(): string
    {
        return rtrim((string) (config('services.sia.public_url') ?: config('services.sia.base_url')), '/');
    }

    private function responseOk($response): bool
    {
        return is_array($response)
            && (
                ($response['success'] ?? false) === true ||
                ($response['status'] ?? false) === true ||
                ($response['status'] ?? null) === 'success'
            );
    }

    private function pickString(...$values): ?string
    {
        foreach ($values as $value) {
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }

            if (is_numeric($value)) {
                return (string) $value;
            }
        }

        return null;
    }

    private function normalizeName(?string $name): string
    {
        $name = strtolower(trim((string) $name));
        $name = preg_replace('/\s+/', ' ', $name);

        return $name ?: '';
    }

    private function guessMimeType(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($ext) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'jpg', 'jpeg' => 'image/jpeg',
            default => 'image/jpeg',
        };
    }

    private function defaultPhotoPath(): string
    {
        $candidates = [
            public_path('images/default-user.png'),
            public_path('images/default-siswa.png'),
            public_path('images/logo-sma2.png'),
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        abort(404, 'File foto default tidak ditemukan.');
    }
}