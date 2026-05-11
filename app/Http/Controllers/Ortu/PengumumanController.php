<?php

namespace App\Http\Controllers\Ortu;

use App\Http\Controllers\Controller;
use App\Models\Pengumuman;
use App\Services\SiaClient;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PengumumanController extends Controller
{
    protected SiaClient $sia;

    private ?array $audienceCache = null;

    public function __construct(SiaClient $sia)
    {
        $this->sia = $sia;
    }

    /**
     * Daftar pengumuman aktif untuk orang tua.
     */
    public function index(): View
    {
        $audience = $this->resolveAudience();

        $pengumuman = $this->resolvePengumumanUntukOrtu($audience);

        return view('ortu.pengumuman.index', [
            'pengumuman' => $pengumuman,
            'tingkatSiswa' => $audience['tingkat'] ?? null,
            'rombelSiswa' => $audience['rombel'] ?? null,
        ]);
    }

    /**
     * Detail pengumuman aktif.
     */
    public function show(Pengumuman $pengumuman): View
    {
        abort_unless($this->isAccessible($pengumuman), 403);

        return view('ortu.pengumuman.show', [
            'pengumuman' => $pengumuman,
        ]);
    }

    /**
     * Preview PDF inline.
     */
    public function pdfView(Pengumuman $pengumuman): BinaryFileResponse|Response
    {
        abort_unless($this->isAccessible($pengumuman), 403);

        $pdfPath = $this->resolvePdfPath($pengumuman);
        $fullPath = Storage::disk('public')->path($pdfPath);

        return response()->file($fullPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="pengumuman-' . $pengumuman->id . '.pdf"',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    /**
     * Download PDF.
     */
    public function pdfDownload(Pengumuman $pengumuman): StreamedResponse
    {
        abort_unless($this->isAccessible($pengumuman), 403);

        $pdfPath = $this->resolvePdfPath($pengumuman);

        return Storage::disk('public')->download(
            $pdfPath,
            'pengumuman-' . $pengumuman->id . '.pdf'
        );
    }

    private function resolvePengumumanUntukOrtu(array $audience): Collection
    {
        return Pengumuman::query()
            ->aktif()
            ->orderByDesc('publish_at')
            ->orderByDesc('id')
            ->get()
            ->filter(function (Pengumuman $item) use ($audience) {
                return $this->matchesAudience($item, $audience);
            })
            ->values();
    }

    /**
     * Cek apakah pengumuman boleh diakses orang tua.
     */
    private function isAccessible(Pengumuman $pengumuman): bool
    {
        if (($pengumuman->status ?? null) !== 'approved') {
            return false;
        }

        if (!(bool) ($pengumuman->is_active ?? false)) {
            return false;
        }

        $now = now();

        if ($pengumuman->publish_at && $pengumuman->publish_at->gt($now)) {
            return false;
        }

        if ($pengumuman->expire_at && $pengumuman->expire_at->lt($now)) {
            return false;
        }

        return $this->matchesAudience($pengumuman, $this->resolveAudience());
    }

    /**
     * Cocokkan target pengumuman dengan tingkat siswa.
     */
    private function matchesAudience(Pengumuman $pengumuman, array $audience): bool
    {
        $scope = $this->normalizeScope($pengumuman->target_scope ?? null);

        if ($scope === 'all') {
            return true;
        }

        if ($scope === 'tingkat') {
            $targetTingkat = $this->normalizeTingkat($pengumuman->target_tingkat ?? null);
            $tingkatSiswa = $this->normalizeTingkat($audience['tingkat'] ?? null);

            if ($targetTingkat === null || $tingkatSiswa === null) {
                return false;
            }

            return $targetTingkat === $tingkatSiswa;
        }

        return false;
    }

    private function normalizeScope($scope): string
    {
        $scope = strtolower(trim((string) $scope));

        return match ($scope) {
            '', 'all', 'semua', 'umum' => 'all',
            'tingkat', 'level' => 'tingkat',
            default => $scope,
        };
    }

    /**
     * Ambil data audiens siswa dari API SIA berdasarkan NIS akun orang tua.
     */
    private function resolveAudience(): array
    {
        if ($this->audienceCache !== null) {
            return $this->audienceCache;
        }

        $user = Auth::user();
        $nis = trim((string) ($user?->sia_user_id ?? ''));

        $siswaDetail = null;

        if ($nis !== '') {
            try {
                $basic = $this->sia->getSiswaByNis($nis);

                if ($this->responseOk($basic) && !empty($basic['data']) && is_array($basic['data'])) {
                    $basicData = $basic['data'];
                    $siswaId = $basicData['id'] ?? null;

                    if ($siswaId) {
                        $detail = $this->sia->masterSiswaDetail($siswaId);

                        if ($this->responseOk($detail) && !empty($detail['data']) && is_array($detail['data'])) {
                            $siswaDetail = $detail['data'];
                        } else {
                            $siswaDetail = $basicData;
                        }
                    } else {
                        $siswaDetail = $basicData;
                    }
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $rombelName = $this->pickString(
            data_get($siswaDetail, 'rombel.nama_rombel'),
            data_get($siswaDetail, 'rombel.nama'),
            data_get($siswaDetail, 'rombel.label'),
            data_get($siswaDetail, 'rombel_aktif.nama_rombel'),
            data_get($siswaDetail, 'rombel_aktif.nama'),
            data_get($siswaDetail, 'rombel_aktif.label'),
            data_get($siswaDetail, 'nama_rombel'),
            data_get($siswaDetail, 'kelas'),
            null
        );

        $tingkat = $this->normalizeTingkat(
            data_get($siswaDetail, 'rombel.nama_rombel'),
            data_get($siswaDetail, 'rombel.nama'),
            data_get($siswaDetail, 'rombel.label'),
            data_get($siswaDetail, 'rombel_aktif.nama_rombel'),
            data_get($siswaDetail, 'rombel_aktif.nama'),
            data_get($siswaDetail, 'rombel_aktif.label'),
            data_get($siswaDetail, 'nama_rombel'),
            data_get($siswaDetail, 'kelas'),
            data_get($siswaDetail, 'tingkat'),
            data_get($siswaDetail, 'kelas_tingkat'),
            data_get($siswaDetail, 'rombel.tingkat'),
            data_get($siswaDetail, 'rombel_aktif.tingkat')
        );

        $this->audienceCache = [
            'nis' => $nis,
            'rombel' => $rombelName,
            'tingkat' => $tingkat,
            'siswa' => $siswaDetail,
        ];

        return $this->audienceCache;
    }

    /**
     * Normalisasi tingkat agar X, XI, dan XII tidak tertukar.
     */
    private function normalizeTingkat(...$values): ?string
    {
        foreach ($values as $value) {
            if (is_array($value)) {
                $nested = $this->normalizeTingkat(
                    $value['nama_rombel'] ?? null,
                    $value['nama'] ?? null,
                    $value['label'] ?? null,
                    $value['rombel'] ?? null,
                    $value['tingkat'] ?? null
                );

                if ($nested !== null) {
                    return $nested;
                }

                continue;
            }

            if (is_object($value)) {
                $nested = $this->normalizeTingkat(
                    $value->nama_rombel ?? null,
                    $value->nama ?? null,
                    $value->label ?? null,
                    $value->rombel ?? null,
                    $value->tingkat ?? null
                );

                if ($nested !== null) {
                    return $nested;
                }

                continue;
            }

            $text = strtoupper(trim((string) $value));

            if ($text === '' || $text === '-') {
                continue;
            }

            $text = str_replace(['-', '_', '.', '/', '\\'], ' ', $text);
            $text = preg_replace('/\s+/', ' ', $text);
            $compact = preg_replace('/[^A-Z0-9]/', '', $text);

            if (
                $compact === '12' ||
                str_contains($compact, 'KELAS12') ||
                str_contains($compact, 'TINGKAT12') ||
                preg_match('/(^|[^0-9])12([^0-9]|$)/', $text)
            ) {
                return 'XII';
            }

            if (
                $compact === '11' ||
                str_contains($compact, 'KELAS11') ||
                str_contains($compact, 'TINGKAT11') ||
                preg_match('/(^|[^0-9])11([^0-9]|$)/', $text)
            ) {
                return 'XI';
            }

            if (
                $compact === '10' ||
                str_contains($compact, 'KELAS10') ||
                str_contains($compact, 'TINGKAT10') ||
                preg_match('/(^|[^0-9])10([^0-9]|$)/', $text)
            ) {
                return 'X';
            }

            if (str_starts_with($compact, 'XII')) {
                return 'XII';
            }

            if (str_starts_with($compact, 'XI')) {
                return 'XI';
            }

            if (str_starts_with($compact, 'X')) {
                return 'X';
            }

            if (preg_match('/\bKELAS\s*XII\b|\bTINGKAT\s*XII\b|\bXII\b/', $text)) {
                return 'XII';
            }

            if (preg_match('/\bKELAS\s*XI\b|\bTINGKAT\s*XI\b|\bXI\b/', $text)) {
                return 'XI';
            }

            if (preg_match('/\bKELAS\s*X\b|\bTINGKAT\s*X\b|\bX\b/', $text)) {
                return 'X';
            }
        }

        return null;
    }

    /**
     * Normalisasi path PDF.
     */
    private function normalizePdfPath(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        $pdfPath = trim((string) $path);
        $pdfPath = str_replace('\\', '/', $pdfPath);
        $pdfPath = preg_replace('#/+#', '/', $pdfPath);
        $pdfPath = ltrim($pdfPath, '/');

        if (str_starts_with($pdfPath, 'public/storage/')) {
            $pdfPath = substr($pdfPath, strlen('public/storage/'));
        }

        if (str_starts_with($pdfPath, 'storage/app/public/')) {
            $pdfPath = substr($pdfPath, strlen('storage/app/public/'));
        }

        if (str_starts_with($pdfPath, 'storage/')) {
            $pdfPath = substr($pdfPath, strlen('storage/'));
        }

        return $pdfPath !== '' ? $pdfPath : null;
    }

    /**
     * Resolve PDF. Jika file hilang tetapi pengumuman approved, PDF dibuat ulang.
     */
    private function resolvePdfPath(Pengumuman $pengumuman): string
    {
        $pdfPath = $this->normalizePdfPath($pengumuman->pdf_path);

        if ($pdfPath && Storage::disk('public')->exists($pdfPath)) {
            return $pdfPath;
        }

        if (($pengumuman->status ?? null) !== 'approved') {
            abort(403, 'PDF hanya tersedia untuk pengumuman yang telah disetujui.');
        }

        $freshPengumuman = $pengumuman->fresh(['author', 'approver']) ?: $pengumuman;
        $pdfPath = $this->generateOfficialPdf($freshPengumuman);

        $pengumuman->forceFill([
            'pdf_path' => $pdfPath,
        ])->save();

        if (!Storage::disk('public')->exists($pdfPath)) {
            abort(404, 'File PDF tidak ditemukan.');
        }

        return $pdfPath;
    }

    private function generateOfficialPdf(Pengumuman $item): string
    {
        $item->loadMissing(['author', 'approver']);

        $path = 'pengumuman/pdf/pengumuman_' . $item->id . '.pdf';

        Storage::disk('public')->makeDirectory('pengumuman/pdf');

        $pdf = Pdf::loadView('pdf.pengumuman_resmi', [
            'item' => $item,
            'capPath' => $this->publicImagePath('images/cap-sma2.png'),
            'ttdPath' => $this->publicImagePath('images/ttd-kepsek.png'),
        ])->setPaper('A4', 'portrait');

        Storage::disk('public')->put($path, $pdf->output());

        return $path;
    }

    private function publicImagePath(string $relativePath): ?string
    {
        $path = public_path($relativePath);

        return is_file($path) ? $path : null;
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
}