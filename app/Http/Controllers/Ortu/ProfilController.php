<?php

namespace App\Http\Controllers\Ortu;

use App\Http\Controllers\Controller;
use App\Services\SiaClient;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
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
        $siswaApi = $this->resolveSiswaProfile($user);
        $previewFoto = is_array($siswaApi) ? $this->resolveSiswaPhotoPath($siswaApi) : null;

        return view('ortu.profil.show', [
            'user' => $user,
            'siswaApi' => $siswaApi,
            'previewFoto' => $previewFoto,
        ]);
    }

    public function photo()
    {
        $user = Auth::user();
        $siswaApi = $this->resolveSiswaProfile($user);

        $defaultFotoPath = $this->defaultPhotoPath();

        if (!is_array($siswaApi) || empty($siswaApi)) {
            return $this->responseImageFile($defaultFotoPath);
        }

        $fotoPath = $this->resolveSiswaPhotoPath($siswaApi);

        if (!$fotoPath || !File::exists($fotoPath) || !File::isFile($fotoPath)) {
            return $this->responseImageFile($defaultFotoPath);
        }

        return $this->responseImageFile($fotoPath);
    }

    private function resolveSiswaProfile($user): ?array
    {
        try {
            $nis = trim((string) ($user->sia_user_id ?? ''));

            if ($nis === '') {
                return null;
            }

            $resp = $this->sia->getSiswaByNis($nis);

            if (
                !is_array($resp) ||
                !(($resp['success'] ?? false) === true ||
                    ($resp['status'] ?? false) === true ||
                    ($resp['status'] ?? null) === 'success') ||
                empty($resp['data']) ||
                !is_array($resp['data'])
            ) {
                return null;
            }

            $basic = $resp['data'];
            $id = $basic['id'] ?? null;

            if ($id) {
                try {
                    $detail = $this->sia->masterSiswaDetail($id);

                    if (
                        is_array($detail) &&
                        (($detail['success'] ?? false) === true ||
                            ($detail['status'] ?? false) === true ||
                            ($detail['status'] ?? null) === 'success') &&
                        !empty($detail['data']) &&
                        is_array($detail['data'])
                    ) {
                        return array_replace_recursive($basic, $detail['data']);
                    }
                } catch (\Throwable $e) {
                    report($e);
                }
            }

            return $basic;
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }

    private function resolveSiswaPhotoPath(array $siswa): ?string
    {
        $fotoDir = public_path('sia/foto_siswa');

        if (!File::isDirectory($fotoDir)) {
            return null;
        }

        $fotoRaw = $this->pickString(
            data_get($siswa, 'foto'),
            data_get($siswa, 'foto_siswa'),
            data_get($siswa, 'foto_url'),
            data_get($siswa, 'photo'),
            data_get($siswa, 'pas_foto'),
            data_get($siswa, 'file_foto'),
            data_get($siswa, 'path_foto'),
            data_get($siswa, 'biodata.foto'),
            data_get($siswa, 'profil.foto'),
            data_get($siswa, 'detail.foto')
        );

        $nis = $this->pickString(data_get($siswa, 'nis'));
        $nisn = $this->pickString(data_get($siswa, 'nisn'));
        $nama = $this->pickString(data_get($siswa, 'nama'));

        $candidateNames = [];

        if ($fotoRaw) {
            $fotoName = $this->normalizePhotoFilename($fotoRaw);

            if ($fotoName) {
                $candidateNames[] = $fotoName;
                $candidateNames[] = basename($fotoName);
                $candidateNames[] = pathinfo($fotoName, PATHINFO_FILENAME);
            }
        }

        if ($nis) {
            $candidateNames[] = $nis;
        }

        if ($nisn) {
            $candidateNames[] = $nisn;
        }

        if ($nama) {
            $candidateNames[] = $this->normalizeHumanFilename($nama);
            $candidateNames[] = Str::slug($nama);
        }

        $candidateNames = collect($candidateNames)
            ->filter(fn($value) => is_string($value) && trim($value) !== '')
            ->map(fn($value) => trim($value))
            ->unique()
            ->values();

        $extensions = ['jpg', 'jpeg', 'png', 'webp', 'JPG', 'JPEG', 'PNG', 'WEBP'];

        foreach ($candidateNames as $name) {
            $name = basename(str_replace('\\', '/', $name));

            if ($name === '') {
                continue;
            }

            $directPath = $fotoDir . DIRECTORY_SEPARATOR . $name;

            if (File::exists($directPath) && File::isFile($directPath)) {
                return $directPath;
            }

            $nameWithoutExt = pathinfo($name, PATHINFO_FILENAME);

            foreach ($extensions as $ext) {
                $pathWithExt = $fotoDir . DIRECTORY_SEPARATOR . $nameWithoutExt . '.' . $ext;

                if (File::exists($pathWithExt) && File::isFile($pathWithExt)) {
                    return $pathWithExt;
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Fallback case-insensitive
        |--------------------------------------------------------------------------
        | Untuk mengatasi nama file hash yang huruf besar-kecilnya berbeda.
        */
        $allFiles = collect(File::files($fotoDir));

        foreach ($candidateNames as $name) {
            $name = basename(str_replace('\\', '/', $name));

            if ($name === '') {
                continue;
            }

            $targetLower = strtolower($name);
            $targetNoExtLower = strtolower(pathinfo($name, PATHINFO_FILENAME));

            $matched = $allFiles->first(function ($file) use ($targetLower, $targetNoExtLower) {
                $filename = $file->getFilename();
                $filenameLower = strtolower($filename);
                $filenameNoExtLower = strtolower(pathinfo($filename, PATHINFO_FILENAME));

                return $filenameLower === $targetLower
                    || $filenameNoExtLower === $targetNoExtLower;
            });

            if ($matched) {
                return $matched->getPathname();
            }
        }

        return null;
    }

    private function normalizePhotoFilename(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $value = urldecode($value);
        $value = str_replace('\\', '/', $value);
        $value = preg_replace('#/+#', '/', $value);

        if (filter_var($value, FILTER_VALIDATE_URL)) {
            $path = parse_url($value, PHP_URL_PATH);
            $value = is_string($path) ? $path : $value;
        }

        $value = ltrim($value, '/');

        $prefixes = [
            'storage/',
            'public/',
            'sia/foto_siswa/',
            'foto_siswa/',
            'storage/sia/foto_siswa/',
            'uploads/foto_siswa/',
            'uploads/siswa/',
            'siswa/',
        ];

        foreach ($prefixes as $prefix) {
            if (Str::startsWith($value, $prefix)) {
                $value = Str::after($value, $prefix);
            }
        }

        $value = basename($value);

        return $value !== '' ? $value : null;
    }

    private function normalizeHumanFilename(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $value = strtolower(trim((string) $value));
        $value = preg_replace('/[^a-z0-9]+/i', '_', $value);
        $value = trim($value, '_');

        return $value !== '' ? $value : null;
    }

    private function responseImageFile(string $path)
    {
        if (!File::exists($path) || !File::isFile($path)) {
            $path = public_path('images/avatar-default.png');
        }

        if (!File::exists($path) || !File::isFile($path)) {
            abort(404);
        }

        $mime = File::mimeType($path) ?: 'image/jpeg';

        return response()->file($path, [
            'Content-Type' => $mime,
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    private function defaultPhotoPath(): string
    {
        if (File::exists(public_path('images/avatar-default.png'))) {
            return public_path('images/avatar-default.png');
        }

        if (File::exists(public_path('images/default-user.png'))) {
            return public_path('images/default-user.png');
        }

        if (File::exists(public_path('images/default-siswa.png'))) {
            return public_path('images/default-siswa.png');
        }

        return public_path('images/avatar-default.png');
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
}