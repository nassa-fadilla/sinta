<?php

namespace App\Http\Controllers\Kepsek;

use App\Http\Controllers\Controller;
use App\Models\Pengumuman;
use App\Services\SiaClient;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

    /**
     * =========================================================
     * INDEX
     * =========================================================
     */
    public function index(Request $request)
    {
        $status = trim((string) $request->query('status', 'pending'));
        $q = trim((string) $request->query('q', ''));

        $items = Pengumuman::query()
            ->with(['author', 'approver'])
            ->when($q !== '', fn($w) => $w->where('judul', 'like', "%{$q}%"))
            ->when($status === 'pending', fn($w) => $w->where('status', 'draft'))
            ->when($status === 'approved', fn($w) => $w->where('status', 'approved'))
            ->when($status === 'rejected', fn($w) => $w->where('status', 'rejected'))
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $tahunAjaranMap = $this->getTahunAjaranMap();

        return view('kepsek.pengumuman.index', [
            'items' => $items,
            'status' => $status,
            'q' => $q,
            'tahunAjaranMap' => $tahunAjaranMap,
        ]);
    }

    /**
     * =========================================================
     * SHOW
     * =========================================================
     */
    public function show($id)
    {
        $item = Pengumuman::query()
            ->with(['author', 'approver'])
            ->findOrFail($id);

        $tahunAjaran = null;

        if (!empty($item->tahun_ajaran_id)) {
            $tahunAjaranMap = $this->getTahunAjaranMap();
            $tahunAjaran = $tahunAjaranMap[(int) $item->tahun_ajaran_id] ?? null;

            if (!$tahunAjaran) {
                $tahunAjaran = $this->getTahunAjaranDetail((int) $item->tahun_ajaran_id);
            }
        }

        return view('kepsek.pengumuman.show', [
            'item' => $item,
            'tahunAjaran' => $tahunAjaran,
        ]);
    }

    /**
     * =========================================================
     * PREVIEW PDF INLINE
     * =========================================================
     *
     * PDF tidak dibuka langsung lewat /storage agar tidak terkena 403
     * pada hosting. File dibaca dari disk public melalui Laravel.
     */
    public function pdfView(Pengumuman $pengumuman): BinaryFileResponse|Response
    {
        $this->authorizePdfAccess($pengumuman);

        $pdfPath = $this->resolvePdfPath($pengumuman);
        $fullPath = Storage::disk('public')->path($pdfPath);

        return response()->file($fullPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="pengumuman-' . $pengumuman->id . '.pdf"',
        ]);
    }

    /**
     * =========================================================
     * DOWNLOAD PDF
     * =========================================================
     *
     * Unduhan juga lewat controller agar konsisten seperti fitur orang tua.
     */
    public function pdfDownload(Pengumuman $pengumuman): StreamedResponse
    {
        $this->authorizePdfAccess($pengumuman);

        $pdfPath = $this->resolvePdfPath($pengumuman);

        return Storage::disk('public')->download(
            $pdfPath,
            'pengumuman-' . $pengumuman->id . '.pdf'
        );
    }

    /**
     * =========================================================
     * APPROVE
     * =========================================================
     */
    public function approve(Pengumuman $pengumuman)
    {
        if ($pengumuman->status !== 'draft') {
            return redirect()
                ->route('kepsek.pengumuman.index')
                ->with('no', 'Pengumuman ini tidak dapat disetujui karena statusnya tidak sesuai.');
        }

        DB::transaction(function () use ($pengumuman) {
            $now = now();

            /*
            |--------------------------------------------------------------------------
            | Catatan Logika Aktif
            |--------------------------------------------------------------------------
            | is_active digunakan sebagai penanda bahwa pengumuman sudah disetujui dan
            | tidak dinonaktifkan. Waktu tampil tetap dikontrol oleh publish_at dan
            | expire_at melalui scopeAktif() pada model Pengumuman.
            |
            | Dengan begitu, pengumuman yang disetujui hari ini tetapi dijadwalkan
            | tayang besok tetap akan muncul otomatis ketika publish_at sudah tercapai.
            |--------------------------------------------------------------------------
            */
            $pengumuman->update([
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => $now,
                'is_active' => true,
                'reject_note' => null,
            ]);

            $pdf = Pdf::loadView('pdf.pengumuman_resmi', [
                'item' => $pengumuman->fresh()->load(['author', 'approver']),
                'capPath' => public_path('images/cap-sma2.png'),
                'ttdPath' => public_path('images/ttd-kepsek.png'),
            ])->setPaper('A4', 'portrait');

            $path = 'pengumuman/pdf/pengumuman_' . $pengumuman->id . '.pdf';

            if (!empty($pengumuman->pdf_path) && Storage::disk('public')->exists($pengumuman->pdf_path)) {
                Storage::disk('public')->delete($pengumuman->pdf_path);
            }

            Storage::disk('public')->put($path, $pdf->output());

            $pengumuman->update([
                'pdf_path' => $path,
            ]);
        });

        return redirect()
            ->route('kepsek.pengumuman.index')
            ->with('ok', 'Pengumuman disetujui.');
    }

    /**
     * =========================================================
     * REJECT
     * =========================================================
     */
    public function reject(Request $request, Pengumuman $pengumuman)
    {
        if ($pengumuman->status !== 'draft') {
            return redirect()
                ->route('kepsek.pengumuman.index')
                ->with('no', 'Pengumuman ini tidak dapat ditolak karena statusnya tidak sesuai.');
        }

        $request->validate([
            'reject_note' => ['required', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($request, $pengumuman) {
            if (!empty($pengumuman->pdf_path) && Storage::disk('public')->exists($pengumuman->pdf_path)) {
                Storage::disk('public')->delete($pengumuman->pdf_path);
            }

            $pengumuman->update([
                'status' => 'rejected',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'is_active' => false,
                'reject_note' => $request->reject_note,
                'pdf_path' => null,
            ]);
        });

        return redirect()
            ->route('kepsek.pengumuman.index')
            ->with('no', 'Pengumuman telah ditolak.');
    }

    /**
     * =========================================================
     * HELPER: AKSES PDF KEPSEK
     * =========================================================
     *
     * Kepsek boleh membuka PDF pengumuman yang sudah memiliki file PDF.
     * File PDF umumnya tersedia setelah pengumuman disetujui.
     */
    private function authorizePdfAccess(Pengumuman $pengumuman): void
    {
        if (empty($pengumuman->pdf_path)) {
            abort(404, 'PDF belum tersedia.');
        }

        if (($pengumuman->status ?? null) !== 'approved') {
            abort(403, 'PDF hanya tersedia untuk pengumuman yang telah disetujui.');
        }
    }

    /**
     * =========================================================
     * HELPER: NORMALISASI PATH PDF
     * =========================================================
     *
     * Mendukung path lama/baru:
     * - pengumuman/pdf/pengumuman_17.pdf
     * - storage/pengumuman/pdf/pengumuman_17.pdf
     * - /storage/pengumuman/pdf/pengumuman_17.pdf
     */
    private function resolvePdfPath(Pengumuman $pengumuman): string
    {
        if (empty($pengumuman->pdf_path)) {
            abort(404, 'PDF belum tersedia.');
        }

        $pdfPath = trim((string) $pengumuman->pdf_path);
        $pdfPath = str_replace('\\', '/', $pdfPath);
        $pdfPath = preg_replace('#/+#', '/', $pdfPath);
        $pdfPath = ltrim($pdfPath, '/');

        if (str_starts_with($pdfPath, 'storage/')) {
            $pdfPath = substr($pdfPath, 8);
        }

        if (!Storage::disk('public')->exists($pdfPath)) {
            abort(404, 'File PDF tidak ditemukan.');
        }

        return $pdfPath;
    }

    /**
     * =========================================================
     * HELPER: GET LIST TAHUN AJARAN DARI API SIA
     * =========================================================
     */
    private function getTahunAjaranMap(): array
    {
        $response = $this->sia->masterTahunAjaran();
        $rows = $this->toList($response['data'] ?? []);

        $map = [];

        foreach ($rows as $row) {
            $id = isset($row['id']) ? (int) $row['id'] : null;

            if (!$id) {
                continue;
            }

            $map[$id] = (object) [
                'id' => $id,
                'nama_tahun' => $row['nama_tahun'] ?? ($row['nama'] ?? '-'),
                'semester' => $row['semester'] ?? '-',
                'status' => $row['status'] ?? null,
            ];
        }

        return $map;
    }

    /**
     * =========================================================
     * HELPER: GET DETAIL TAHUN AJARAN DARI API SIA
     * =========================================================
     */
    private function getTahunAjaranDetail(int $id): ?object
    {
        $response = $this->sia->masterTahunAjaranDetail($id);

        if (($response['status'] ?? false) !== true || empty($response['data'])) {
            return null;
        }

        $row = $this->toArray($response['data']);

        return (object) [
            'id' => isset($row['id']) ? (int) $row['id'] : null,
            'nama_tahun' => $row['nama_tahun'] ?? ($row['nama'] ?? '-'),
            'semester' => $row['semester'] ?? '-',
            'status' => $row['status'] ?? null,
        ];
    }

    /**
     * =========================================================
     * HELPER: NORMALISASI LIST
     * =========================================================
     */
    private function toList($value): array
    {
        if ($value instanceof Collection) {
            return $value->map(fn($item) => $this->toArray($item))->all();
        }

        if (is_array($value)) {
            return array_map(fn($item) => $this->toArray($item), $value);
        }

        if (is_object($value)) {
            return array_map(fn($item) => $this->toArray($item), (array) $value);
        }

        return [];
    }

    /**
     * =========================================================
     * HELPER: NORMALISASI ARRAY
     * =========================================================
     */
    private function toArray($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_object($value)) {
            return (array) $value;
        }

        return [];
    }
}