<?php

namespace App\Http\Controllers\Guru;

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
        $guruApi = $this->resolveGuruProfile($user);

        return view('guru.profil.show', [
            'user' => $user,
            'guruApi' => $guruApi,
        ]);
    }

    public function photo()
    {
        $user = Auth::user();
        $guruApi = $this->resolveGuruProfile($user);

        $defaultFotoPath = public_path(
            file_exists(public_path('images/default-user.png'))
            ? 'images/default-user.png'
            : 'images/default-siswa.png'
        );

        if (!is_array($guruApi) || empty($guruApi)) {
            return response()->file($defaultFotoPath, [
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 1. Cari foto guru di file lokal proyek SINTA
        |--------------------------------------------------------------------------
        | Ini penting karena foto guru dapat tersimpan di public/sia/foto_guru,
        | bukan selalu berupa URL storage dari API SIA.
        */
        $localPath = $this->resolveGuruPhotoLocalPath($guruApi);

        if ($localPath && file_exists($localPath) && is_file($localPath)) {
            return response()->file($localPath, [
                'Content-Type' => $this->guessMimeType($localPath),
                'Cache-Control' => 'public, max-age=3600',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Jika tidak ada file lokal, coba ambil dari URL/API SIA
        |--------------------------------------------------------------------------
        */
        $fotoUrl = $this->resolveGuruPhotoUrl($guruApi);

        if ($fotoUrl) {
            try {
                $res = Http::timeout(20)->get($fotoUrl);

                if ($res->successful() && $res->body() !== '') {
                    $contentType = $res->header('Content-Type', 'image/jpeg');

                    return response($res->body(), 200)
                        ->header('Content-Type', $contentType)
                        ->header('Cache-Control', 'public, max-age=3600');
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Fallback default
        |--------------------------------------------------------------------------
        */
        return response()->file($defaultFotoPath, [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    private function resolveGuruProfile($user): ?array
    {
        try {
            $lookupKey = trim((string) ($user->sia_user_id ?? ''));

            /*
            |--------------------------------------------------------------------------
            | Prioritas utama: identifier akun SIA
            |--------------------------------------------------------------------------
            | Untuk role guru/wali kelas, sia_user_id biasanya berisi NUPTK/NIP/id
            | yang dipakai untuk mencocokkan data guru dari API SIA.
            */
            if ($lookupKey !== '') {
                $resp = $this->sia->getGuruByKey($lookupKey);

                if ($this->responseOk($resp) && !empty($resp['data']) && is_array($resp['data'])) {
                    return $resp['data'];
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Fallback 1: cari berdasarkan email
            |--------------------------------------------------------------------------
            */
            if (!empty($user->email)) {
                $resp = $this->sia->getGuru($user->email);

                if (!empty($resp['data']) && is_array($resp['data'])) {
                    foreach ($resp['data'] as $row) {
                        if (is_array($row) && ($row['email'] ?? null) === $user->email) {
                            return $row;
                        }
                    }

                    if (!empty($resp['data'][0]) && is_array($resp['data'][0])) {
                        return $resp['data'][0];
                    }
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Fallback 2: cari berdasarkan nama
            |--------------------------------------------------------------------------
            */
            if (!empty($user->name)) {
                $resp = $this->sia->getGuru($user->name);

                if (!empty($resp['data']) && is_array($resp['data'])) {
                    foreach ($resp['data'] as $row) {
                        if (is_array($row) && ($row['nama'] ?? null) === $user->name) {
                            return $row;
                        }
                    }

                    if (!empty($resp['data'][0]) && is_array($resp['data'][0])) {
                        return $resp['data'][0];
                    }
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return null;
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
            data_get($guru, 'profil.foto'),
            data_get($guru, 'detail.foto')
        );

        if (!$fotoRaw) {
            return null;
        }

        $foto = trim((string) $fotoRaw);
        $foto = str_replace('\\', '/', $foto);
        $foto = preg_replace('#/+#', '/', $foto);
        $foto = ltrim($foto, '/');

        /*
        |--------------------------------------------------------------------------
        | Jika API mengirim URL, ambil basename untuk dicari di folder lokal juga.
        |--------------------------------------------------------------------------
        */
        if (filter_var($foto, FILTER_VALIDATE_URL)) {
            $pathFromUrl = parse_url($foto, PHP_URL_PATH);
            $basename = basename((string) $pathFromUrl);
        } else {
            $basename = basename($foto);
        }

        $candidates = [];

        /*
        |--------------------------------------------------------------------------
        | Kandidat path sesuai kemungkinan struktur folder di proyek SINTA
        |--------------------------------------------------------------------------
        */
        if ($foto !== '') {
            $candidates[] = public_path($foto);
            $candidates[] = public_path('storage/' . $foto);
            $candidates[] = public_path('sia/' . $foto);
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
        | Tambahan pencarian berdasarkan ID/NIP/NUPTK jika nama file tidak persis
        |--------------------------------------------------------------------------
        | Berguna jika file lokal bernama NUPTK.jpg atau NIP.png, sedangkan API
        | hanya mengirim identitas guru.
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

        foreach ($candidates as $path) {
            if ($path && file_exists($path) && is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    private function resolveGuruPhotoUrl(array $guru): ?string
    {
        $fotoUrl = $this->pickString(
            $guru['foto_url'] ?? null,
            $guru['photo_url'] ?? null,
            $guru['url_foto'] ?? null,
            data_get($guru, 'profil.foto_url'),
            data_get($guru, 'detail.foto_url')
        );

        if ($fotoUrl && filter_var($fotoUrl, FILTER_VALIDATE_URL)) {
            return $fotoUrl;
        }

        $foto = $this->pickString(
            $guru['foto'] ?? null,
            $guru['photo'] ?? null,
            $guru['foto_guru'] ?? null,
            $guru['foto_path'] ?? null,
            $guru['path_foto'] ?? null,
            $guru['avatar'] ?? null,
            data_get($guru, 'profil.foto'),
            data_get($guru, 'detail.foto')
        );

        if (!$foto) {
            return null;
        }

        $foto = trim((string) $foto);
        $foto = str_replace('\\', '/', $foto);
        $foto = preg_replace('#/+#', '/', $foto);
        $foto = ltrim($foto, '/');

        if (filter_var($foto, FILTER_VALIDATE_URL)) {
            return $foto;
        }

        $baseUrl = rtrim((string) config('services.sia.base_url'), '/');

        if ($baseUrl === '') {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Beberapa kemungkinan URL storage SIA
        |--------------------------------------------------------------------------
        */
        if (Str::startsWith($foto, ['storage/', 'uploads/', 'foto_guru/', 'guru/'])) {
            return $baseUrl . '/' . $foto;
        }

        return $baseUrl . '/storage/' . $foto;
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
}