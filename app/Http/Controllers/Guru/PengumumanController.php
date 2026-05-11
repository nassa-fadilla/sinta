<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Pengumuman;
use App\Services\SiaClient;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PengumumanController extends Controller
{
    protected SiaClient $sia;

    public function __construct(SiaClient $sia)
    {
        $this->sia = $sia;
    }

    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $jenis = trim((string) $request->query('jenis', ''));

        $rombelBinaan = $this->resolveRombelBinaanGuru();
        $pengumuman = $this->resolvePengumumanUntukGuru($rombelBinaan);

        if ($q !== '') {
            $pengumuman = $pengumuman
                ->filter(function ($item) use ($q) {
                    return str_contains(
                        mb_strtolower((string) ($item->judul ?? '')),
                        mb_strtolower($q)
                    );
                })
                ->values();
        }

        if ($jenis !== '') {
            $pengumuman = $pengumuman
                ->filter(fn($item) => (string) ($item->jenis ?? '') === $jenis)
                ->values();
        }

        $items = $this->paginateCollection(
            $pengumuman,
            10,
            $request->query(),
            $request->url()
        );

        return view('guru.pengumuman.index', compact('items'));
    }

    public function show(Pengumuman $pengumuman): View
    {
        abort_unless($this->isAccessible($pengumuman), 403);

        return view('guru.pengumuman.show', [
            'item' => $pengumuman,
        ]);
    }

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

    public function pdfDownload(Pengumuman $pengumuman): StreamedResponse
    {
        abort_unless($this->isAccessible($pengumuman), 403);

        $pdfPath = $this->resolvePdfPath($pengumuman);

        return Storage::disk('public')->download(
            $pdfPath,
            'pengumuman-' . $pengumuman->id . '.pdf'
        );
    }

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

        $rombelBinaan = $this->resolveRombelBinaanGuru();

        return $this->pengumumanCocokUntukRombel($pengumuman, $rombelBinaan);
    }

    private function resolvePengumumanUntukGuru(?array $rombel): Collection
    {
        return Pengumuman::query()
            ->aktif()
            ->orderByDesc('publish_at')
            ->orderByDesc('id')
            ->get()
            ->filter(function ($item) use ($rombel) {
                return $this->pengumumanCocokUntukRombel($item, $rombel);
            })
            ->values();
    }

    private function pengumumanCocokUntukRombel(Pengumuman $pengumuman, ?array $rombel): bool
    {
        $scope = $this->normalizeScope($pengumuman->target_scope ?? null);

        if ($scope === 'all') {
            return true;
        }

        if ($scope === 'tingkat') {
            $tingkatRombel = $this->resolveTingkatRombel($rombel);
            $targetTingkat = $this->normalizeTingkat($pengumuman->target_tingkat ?? null);

            if ($tingkatRombel === null || $targetTingkat === null) {
                return false;
            }

            return $tingkatRombel === $targetTingkat;
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

    private function resolveRombelBinaanGuru(): ?array
    {
        $user = Auth::user();

        if (!$user) {
            return null;
        }

        $guru = $this->resolveGuruLogin($user);

        if (!$guru) {
            return null;
        }

        $rombel = null;

        try {
            $resp = $this->sia->masterRombel(null, [
                'guru_id' => $guru['id'] ?? null,
                'aktif' => 1,
            ]);

            $list = collect($this->extractListData($resp));

            $rombel = $list->first(function ($row) use ($guru) {
                return $this->isRombelMilikGuru($row, $guru);
            });
        } catch (\Throwable $e) {
            report($e);
        }

        if (!$rombel) {
            try {
                $resp = $this->sia->masterRombel(null, [
                    'guru_id' => $guru['id'] ?? null,
                ]);

                $list = collect($this->extractListData($resp));

                $rombel = $list->first(function ($row) use ($guru) {
                    return $this->isRombelMilikGuru($row, $guru);
                });
            } catch (\Throwable $e) {
                report($e);
            }
        }

        if (!$rombel || !is_array($rombel)) {
            return null;
        }

        $rombel = $this->normalizeRombelRow($rombel);

        if (!empty($rombel['id'])) {
            try {
                $detailResp = $this->sia->masterRombelDetail($rombel['id']);
                $detailData = $this->extractData($detailResp);

                if (!empty($detailData)) {
                    $detail = $this->normalizeRombelRow($detailData);

                    $rombel = array_merge($rombel, array_filter($detail, function ($value) {
                        return $value !== null && $value !== '' && $value !== '-';
                    }));
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $rombel['tingkat'] = $this->resolveTingkatRombel($rombel);

        return $rombel;
    }

    private function resolveGuruLogin($user): ?array
    {
        $identifier = trim((string) ($user->sia_user_id ?? ''));
        $name = trim((string) ($user->name ?? ''));
        $email = trim((string) ($user->email ?? ''));

        if ($identifier !== '') {
            try {
                $resp = $this->sia->getGuruByKey($identifier);
                $data = $this->extractData($resp);

                if (!empty($data)) {
                    return $this->normalizeGuruRow($data);
                }
            } catch (\Throwable $e) {
                report($e);
            }

            try {
                $resp = $this->sia->masterGuru($identifier);
                $list = collect($this->extractListData($resp));

                $match = $list->first(function ($row) use ($identifier) {
                    $guru = $this->normalizeGuruRow($row);

                    return (string) ($guru['id'] ?? '') === $identifier
                        || (string) ($guru['nip'] ?? '') === $identifier
                        || (string) ($guru['nuptk'] ?? '') === $identifier;
                });

                if ($match) {
                    return $this->normalizeGuruRow($match);
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        if ($name !== '') {
            try {
                $resp = $this->sia->masterGuru($name);
                $list = collect($this->extractListData($resp));

                $match = $list->first(function ($row) use ($name, $email) {
                    $guru = $this->normalizeGuruRow($row);

                    $namaMatch = $this->normalizeName($guru['nama'] ?? '') === $this->normalizeName($name);
                    $emailMatch = $email !== ''
                        && !empty($guru['email'])
                        && mb_strtolower((string) $guru['email']) === mb_strtolower($email);

                    return $namaMatch || $emailMatch;
                });

                if ($match) {
                    return $this->normalizeGuruRow($match);
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return null;
    }

    private function isRombelMilikGuru($row, array $guru): bool
    {
        $rombel = $this->normalizeRombelRow($row);

        $guruId = trim((string) ($guru['id'] ?? ''));
        $guruNip = trim((string) ($guru['nip'] ?? ''));
        $guruNuptk = trim((string) ($guru['nuptk'] ?? ''));
        $guruNama = $this->normalizeName($guru['nama'] ?? '');

        $waliId = trim((string) ($rombel['wali_kelas_id'] ?? ''));
        $guruIdAlt = trim((string) ($rombel['guru_id'] ?? ''));
        $waliNip = trim((string) ($rombel['wali_kelas_nip'] ?? ''));
        $waliNuptk = trim((string) ($rombel['wali_kelas_nuptk'] ?? ''));
        $waliNama = $this->normalizeName($rombel['wali_kelas'] ?? '');

        if ($guruId !== '' && ($waliId === $guruId || $guruIdAlt === $guruId)) {
            return true;
        }

        if ($guruNip !== '' && $waliNip !== '' && $guruNip === $waliNip) {
            return true;
        }

        if ($guruNuptk !== '' && $waliNuptk !== '' && $guruNuptk === $waliNuptk) {
            return true;
        }

        if ($guruNama !== '' && $waliNama !== '' && $guruNama === $waliNama) {
            return true;
        }

        return false;
    }

    private function resolveTingkatRombel(?array $rombel): ?string
    {
        if (!$rombel) {
            return null;
        }

        return $this->normalizeTingkat(
            $rombel['nama_rombel'] ?? null,
            $rombel['nama'] ?? null,
            $rombel['label'] ?? null,
            $rombel['rombel'] ?? null,
            $rombel['tingkat'] ?? null
        );
    }

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

    private function normalizeGuruRow($row): array
    {
        $row = $this->arr($row);

        return [
            'id' => $row['id'] ?? $row['guru_id'] ?? null,
            'nama' => $this->pickString(
                $row['nama'] ?? null,
                $row['name'] ?? null,
                $row['nama_guru'] ?? null,
                ''
            ),
            'nip' => $this->pickString(
                $row['nip'] ?? null,
                $row['nomor_induk'] ?? null,
                ''
            ),
            'nuptk' => $this->pickString(
                $row['nuptk'] ?? null,
                ''
            ),
            'email' => $this->pickString(
                $row['email'] ?? null,
                ''
            ),
        ];
    }

    private function normalizeRombelRow($row): array
    {
        $row = $this->arr($row);

        $waliRaw = $row['wali_kelas']
            ?? $row['walikelas']
            ?? $row['wali']
            ?? $row['guru']
            ?? null;

        $wali = $this->arr($waliRaw);

        $namaRombel = $this->pickString(
            $row['nama_rombel'] ?? null,
            $row['nama'] ?? null,
            $row['label'] ?? null,
            $row['rombel'] ?? null,
            '-'
        );

        return [
            'id' => $row['id']
                ?? $row['rombel_id']
                ?? null,

            'nama_rombel' => $namaRombel,

            'tingkat' => $this->normalizeTingkat(
                $namaRombel,
                $row['tingkat'] ?? null
            ),

            'wali_kelas_id' => $row['wali_kelas_id']
                ?? $row['walikelas_id']
                ?? $row['guru_id']
                ?? $wali['id']
                ?? $wali['guru_id']
                ?? null,

            'guru_id' => $row['guru_id']
                ?? $wali['id']
                ?? $wali['guru_id']
                ?? null,

            'wali_kelas' => $this->pickString(
                $row['nama_wali_kelas'] ?? null,
                $row['wali_kelas_nama'] ?? null,
                $row['walikelas_nama'] ?? null,
                is_string($waliRaw) ? $waliRaw : null,
                $wali['nama'] ?? null,
                $wali['name'] ?? null,
                $wali['nama_guru'] ?? null,
                ''
            ),

            'wali_kelas_nip' => $this->pickString(
                $row['wali_kelas_nip'] ?? null,
                $row['nip_wali_kelas'] ?? null,
                $wali['nip'] ?? null,
                ''
            ),

            'wali_kelas_nuptk' => $this->pickString(
                $row['wali_kelas_nuptk'] ?? null,
                $row['nuptk_wali_kelas'] ?? null,
                $wali['nuptk'] ?? null,
                ''
            ),
        ];
    }

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

    private function paginateCollection(Collection $collection, int $perPage, array $query = [], string $path = null): LengthAwarePaginator
    {
        $page = LengthAwarePaginator::resolveCurrentPage();

        $items = $collection
            ->slice(($page - 1) * $perPage, $perPage)
            ->values();

        return new LengthAwarePaginator(
            $items,
            $collection->count(),
            $perPage,
            $page,
            [
                'path' => $path ?: request()->url(),
                'query' => $query,
            ]
        );
    }

    private function extractData($response)
    {
        if (!is_array($response)) {
            return [];
        }

        $data = $response['data'] ?? [];

        return is_array($data) ? $data : [];
    }

    private function extractListData($response): array
    {
        $data = $this->extractData($response);

        if (empty($data)) {
            return [];
        }

        if (array_is_list($data)) {
            return $data;
        }

        if (isset($data['data']) && is_array($data['data'])) {
            return $data['data'];
        }

        if (isset($data['items']) && is_array($data['items'])) {
            return $data['items'];
        }

        if (isset($data['rows']) && is_array($data['rows'])) {
            return $data['rows'];
        }

        return [$data];
    }

    private function arr($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_object($value)) {
            return get_object_vars($value);
        }

        return [];
    }

    private function pickString(...$values): string
    {
        foreach ($values as $value) {
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }

            if (is_numeric($value)) {
                return (string) $value;
            }
        }

        return '';
    }

    private function normalizeName(?string $value): string
    {
        $value = mb_strtolower(trim((string) $value));
        $value = preg_replace('/\s+/', ' ', $value);

        $replacements = [
            'm. pd' => 'm.pd',
            's. pd' => 's.pd',
            'm. si' => 'm.si',
            's. kom' => 's.kom',
            'm. kom' => 'm.kom',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $value ?? '');
    }
}