<?php

namespace App\Http\Controllers\Kepsek;

use App\Http\Controllers\Controller;
use App\Services\SiaClient;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

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
            return response()->file($defaultFotoPath);
        }

        $fotoUrl = $this->resolveGuruPhotoUrl($guruApi);

        if (!$fotoUrl) {
            return response()->file($defaultFotoPath);
        }

        try {
            $res = Http::timeout(20)
                ->withoutVerifying()
                ->get($fotoUrl);

            if (!$res->successful() || empty($res->body())) {
                return response()->file($defaultFotoPath);
            }

            $contentType = $res->header('Content-Type', 'image/jpeg');

            if (!str_contains(strtolower($contentType), 'image')) {
                return response()->file($defaultFotoPath);
            }

            return response($res->body(), 200)
                ->header('Content-Type', $contentType)
                ->header('Cache-Control', 'public, max-age=3600');
        } catch (\Throwable $e) {
            report($e);
            return response()->file($defaultFotoPath);
        }
    }

    private function resolveKepsekGuru($user): ?array
    {
        $lookupKey = null;

        if (!empty($user?->sia_user_id)) {
            $lookupKey = trim((string) $user->sia_user_id);
        } elseif (!empty(config('services.sia.kepsek_nuptk'))) {
            $lookupKey = trim((string) config('services.sia.kepsek_nuptk'));
        }

        try {
            if ($lookupKey !== '') {
                $resp = $this->sia->getGuruByKey($lookupKey);

                if (
                    is_array($resp) &&
                    (($resp['status'] ?? null) === true ||
                        ($resp['status'] ?? null) === 'success' ||
                        ($resp['success'] ?? null) === true) &&
                    !empty($resp['data']) &&
                    is_array($resp['data'])
                ) {
                    return $resp['data'];
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
                        if (($row['email'] ?? null) === $user->email) {
                            return $row;
                        }
                    }

                    if (!empty($resp['data'][0]) && is_array($resp['data'][0])) {
                        return $resp['data'][0];
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
                        if (($row['nama'] ?? null) === $user->name) {
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
            return null;
        }

        return null;
    }

    private function resolveGuruPhotoUrl(array $guru): ?string
    {
        $fotoUrl = trim((string) ($guru['foto_url'] ?? ''));

        if ($fotoUrl !== '' && filter_var($fotoUrl, FILTER_VALIDATE_URL)) {
            return $fotoUrl;
        }

        $foto = trim((string) ($guru['foto'] ?? ''));

        if ($foto === '') {
            return null;
        }

        $foto = str_replace('\\', '/', $foto);
        $foto = preg_replace('#/+#', '/', $foto);
        $foto = ltrim($foto, '/');

        if ($foto === '') {
            return null;
        }

        if (filter_var($foto, FILTER_VALIDATE_URL)) {
            return $foto;
        }

        /*
        |--------------------------------------------------------------------------
        | Normalisasi path foto dari SIA
        |--------------------------------------------------------------------------
        | Jika API mengirim:
        | - foto_guru/nama.jpg
        | - storage/foto_guru/nama.jpg
        | - public/storage/foto_guru/nama.jpg
        | Tetap diarahkan menjadi base_url/storage/foto_guru/nama.jpg
        */
        $foto = preg_replace('#^public/#', '', $foto);
        $foto = preg_replace('#^storage/#', '', $foto);
        $foto = ltrim($foto, '/');

        $baseUrl = rtrim((string) config('services.sia.base_url'), '/');

        if ($baseUrl === '') {
            return null;
        }

        return $baseUrl . '/storage/' . $foto;
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