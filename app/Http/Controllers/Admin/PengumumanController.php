<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pengumuman;
use App\Services\SiaClient;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
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
     * INDEX
     */
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $jenis = trim((string) $request->query('jenis', ''));
        $status = trim((string) $request->query('status', ''));

        $items = Pengumuman::with(['author', 'approver'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where('judul', 'like', "%{$q}%");
            })
            ->when($jenis !== '', function ($query) use ($jenis) {
                $query->where('jenis', $jenis);
            })
            ->when($status !== '', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        $tahunAjaranMap = $this->getTahunAjaranMapFromItems($items->getCollection());

        return view('admin.pengumuman.index', [
            'items' => $items,
            'tahunAjaranMap' => $tahunAjaranMap,
        ]);
    }

    /**
     * CREATE
     */
    public function create()
    {
        $tahunAjarans = $this->getTahunAjaranOptions();
        $tingkatList = ['X', 'XI', 'XII'];

        return view('admin.pengumuman.create', compact('tahunAjarans', 'tingkatList'));
    }

    /**
     * STORE
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'judul' => ['required', 'string', 'max:150'],
            'isi' => ['required', 'string'],
            'jenis' => ['required', 'string', 'max:30'],
            'target_scope' => ['required', Rule::in(['all', 'tingkat'])],
            'target_tingkat' => ['nullable', 'string', Rule::in(['X', 'XI', 'XII'])],
            'tahun_ajaran_id' => ['nullable', 'integer'],
            'publish_at' => ['required', 'date'],
            'expire_at' => ['nullable', 'date', 'after_or_equal:publish_at'],
        ], [
            'publish_at.required' => 'Waktu mulai tayang wajib diisi.',
            'publish_at.date' => 'Format waktu mulai tayang tidak valid.',
            'expire_at.date' => 'Format waktu akhir tayang tidak valid.',
            'expire_at.after_or_equal' => 'Waktu akhir tayang harus sama atau setelah waktu mulai tayang.',
        ]);

        if (!empty($data['tahun_ajaran_id']) && !$this->isValidTahunAjaranId((int) $data['tahun_ajaran_id'])) {
            return back()
                ->withErrors(['tahun_ajaran_id' => 'Tahun ajaran tidak valid atau tidak ditemukan di SIA.'])
                ->withInput();
        }

        if ($data['target_scope'] !== 'tingkat') {
            $data['target_tingkat'] = null;
        }

        $data['is_active'] = false;
        $data['status'] = 'draft';
        $data['created_by'] = auth()->id();

        Pengumuman::create($data);

        return redirect()
            ->route('admin.pengumuman.index')
            ->with('created', 'Pengumuman berhasil dibuat sebagai draft dan menunggu persetujuan Kepala Sekolah.');
    }

    /**
     * SHOW
     */
    public function show(Pengumuman $pengumuman)
    {
        $pengumuman->load(['author', 'approver']);

        $tahunAjaran = null;
        if ($pengumuman->tahun_ajaran_id) {
            $tahunAjaran = $this->getTahunAjaranDetail((int) $pengumuman->tahun_ajaran_id);
        }

        return view('admin.pengumuman.show', [
            'item' => $pengumuman,
            'tahunAjaran' => $tahunAjaran,
        ]);
    }

    /**
     * EDIT
     */
    public function edit(Pengumuman $pengumuman)
    {
        $tahunAjarans = $this->getTahunAjaranOptions();
        $tingkatList = ['X', 'XI', 'XII'];

        return view('admin.pengumuman.edit', [
            'item' => $pengumuman,
            'tahunAjarans' => $tahunAjarans,
            'tingkatList' => $tingkatList,
        ]);
    }

    /**
     * UPDATE
     */
    public function update(Request $request, Pengumuman $pengumuman)
    {
        if ($pengumuman->status === 'approved') {
            abort(403, 'Pengumuman yang sudah disetujui tidak dapat diubah.');
        }

        $data = $request->validate([
            'judul' => ['required', 'string', 'max:150'],
            'isi' => ['required', 'string'],
            'jenis' => ['required', 'string', 'max:30'],
            'target_scope' => ['required', Rule::in(['all', 'tingkat'])],
            'target_tingkat' => ['nullable', 'string', Rule::in(['X', 'XI', 'XII'])],
            'tahun_ajaran_id' => ['nullable', 'integer'],
            'publish_at' => ['required', 'date'],
            'expire_at' => ['nullable', 'date', 'after_or_equal:publish_at'],
        ], [
            'publish_at.required' => 'Waktu mulai tayang wajib diisi.',
            'publish_at.date' => 'Format waktu mulai tayang tidak valid.',
            'expire_at.date' => 'Format waktu akhir tayang tidak valid.',
            'expire_at.after_or_equal' => 'Waktu akhir tayang harus sama atau setelah waktu mulai tayang.',
        ]);

        if (!empty($data['tahun_ajaran_id']) && !$this->isValidTahunAjaranId((int) $data['tahun_ajaran_id'])) {
            return back()
                ->withErrors(['tahun_ajaran_id' => 'Tahun ajaran tidak valid atau tidak ditemukan di SIA.'])
                ->withInput();
        }

        if ($data['target_scope'] !== 'tingkat') {
            $data['target_tingkat'] = null;
        }

        $oldPdfPath = $this->normalizePdfPath($pengumuman->pdf_path);

        if ($oldPdfPath && Storage::disk('public')->exists($oldPdfPath)) {
            Storage::disk('public')->delete($oldPdfPath);
        }

        $data['status'] = 'draft';
        $data['is_active'] = false;
        $data['approved_at'] = null;
        $data['approved_by'] = null;
        $data['reject_note'] = null;
        $data['pdf_path'] = null;

        $pengumuman->update($data);

        return redirect()
            ->route('admin.pengumuman.index')
            ->with('updated', 'Pengumuman berhasil diperbarui dan diajukan kembali ke Kepala Sekolah.');
    }

    /**
     * DESTROY
     */
    public function destroy(Pengumuman $pengumuman)
    {
        if ($pengumuman->status === 'approved') {
            return redirect()
                ->route('admin.pengumuman.show', $pengumuman)
                ->with('no', 'Pengumuman yang sudah disetujui tidak dapat dihapus.');
        }

        if (!in_array($pengumuman->status, ['draft', 'rejected'], true)) {
            return redirect()
                ->route('admin.pengumuman.show', $pengumuman)
                ->with('no', 'Pengumuman ini tidak dapat dihapus karena statusnya tidak sesuai.');
        }

        $oldPdfPath = $this->normalizePdfPath($pengumuman->pdf_path);

        if ($oldPdfPath && Storage::disk('public')->exists($oldPdfPath)) {
            Storage::disk('public')->delete($oldPdfPath);
        }

        $pengumuman->delete();

        return redirect()
            ->route('admin.pengumuman.index')
            ->with('deleted', 'Pengumuman berhasil dihapus.');
    }

    /**
     * PREVIEW PDF INLINE
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
     * DOWNLOAD PDF
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
     * VALIDASI AKSES PDF ADMIN
     */
    private function authorizePdfAccess(Pengumuman $pengumuman): void
    {
        if (($pengumuman->status ?? null) !== 'approved') {
            abort(403, 'PDF hanya tersedia untuk pengumuman yang sudah disetujui.');
        }

        if (empty($pengumuman->pdf_path)) {
            abort(404, 'PDF belum tersedia.');
        }
    }

    /**
     * NORMALISASI PATH PDF
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

        if (str_starts_with($pdfPath, 'storage/')) {
            $pdfPath = substr($pdfPath, 8);
        }

        return $pdfPath !== '' ? $pdfPath : null;
    }

    /**
     * RESOLVE PDF PATH
     */
    private function resolvePdfPath(Pengumuman $pengumuman): string
    {
        $pdfPath = $this->normalizePdfPath($pengumuman->pdf_path);

        if (!$pdfPath) {
            abort(404, 'PDF belum tersedia.');
        }

        if (!Storage::disk('public')->exists($pdfPath)) {
            abort(404, 'File PDF tidak ditemukan.');
        }

        return $pdfPath;
    }

    /**
     * GENERATE PDF RESMI
     */
    private function generateOfficialPdf(Pengumuman $item): string
    {
        $pdf = Pdf::loadView('pdf.pengumuman_resmi', [
            'item' => $item->load('author', 'approver'),
            'capPath' => public_path('images/cap-sma2.png'),
            'ttdPath' => public_path('images/ttd-kepsek.png'),
        ])->setPaper('A4', 'portrait');

        $path = 'pengumuman/pdf/pengumuman_' . $item->id . '.pdf';

        Storage::disk('public')->put($path, $pdf->output());

        return $path;
    }

    /**
     * OPTIONS TAHUN AJARAN DARI SIA
     */
    private function getTahunAjaranOptions(): Collection
    {
        $response = $this->sia->masterTahunAjaran();
        $rows = $this->toList($response['data'] ?? []);

        return collect($rows)
            ->map(function ($row) {
                return (object) [
                    'id' => $row['id'] ?? null,
                    'nama_tahun' => $row['nama_tahun'] ?? ($row['nama'] ?? '-'),
                    'semester' => $row['semester'] ?? '-',
                    'status' => $row['status'] ?? null,
                ];
            })
            ->sortByDesc(function ($item) {
                return strtolower((string) $item->status) === 'aktif' ? 1 : 0;
            })
            ->values();
    }

    /**
     * DETAIL TAHUN AJARAN DARI SIA
     */
    private function getTahunAjaranDetail(int $id): ?object
    {
        $response = $this->sia->masterTahunAjaranDetail($id);

        if (($response['status'] ?? false) !== true || empty($response['data'])) {
            return null;
        }

        $row = $this->toArray($response['data']);

        return (object) [
            'id' => $row['id'] ?? null,
            'nama_tahun' => $row['nama_tahun'] ?? ($row['nama'] ?? '-'),
            'semester' => $row['semester'] ?? '-',
            'status' => $row['status'] ?? null,
        ];
    }

    /**
     * MAP TAHUN AJARAN UNTUK LIST
     */
    private function getTahunAjaranMapFromItems(Collection $items): array
    {
        $ids = $items->pluck('tahun_ajaran_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $map = [];

        foreach ($ids as $id) {
            $detail = $this->getTahunAjaranDetail((int) $id);
            if ($detail) {
                $map[$id] = $detail;
            }
        }

        return $map;
    }

    /**
     * VALIDASI TAHUN AJARAN
     */
    private function isValidTahunAjaranId(int $id): bool
    {
        $response = $this->sia->masterTahunAjaranDetail($id);

        return ($response['status'] ?? false) === true
            && !empty($response['data']);
    }

    /**
     * NORMALISASI LIST
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
     * NORMALISASI ARRAY
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