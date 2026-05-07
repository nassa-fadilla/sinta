<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Survei;
use App\Models\SurveiPertanyaan;
use App\Models\SurveiOpsi;
use App\Models\SurveiRespon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class SurveiController extends Controller
{
    /** ===========================================================
     *  INDEX
     *  =========================================================== */
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $survei = Survei::withCount('pertanyaan')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('judul', 'like', "%{$q}%")
                        ->orWhere('deskripsi', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        return view('admin.survei.index', compact('survei', 'q'));
    }

    /** ===========================================================
     *  CREATE (BUILDER FORM)
     *  =========================================================== */
    public function create()
    {
        return view('admin.survei.create');
    }

    /** ===========================================================
     *  STORE (SIMPAN SURVEI LENGKAP DARI BUILDER)
     *  =========================================================== */
    public function store(Request $request)
    {
        $data = json_decode($request->payload, true);

        if (!$data || !isset($data['judul'])) {
            return back()->withErrors(['msg' => 'Data survei tidak valid. Pastikan semua form sudah diisi.']);
        }

        DB::transaction(function () use ($data) {
            $survei = Survei::create([
                'judul' => $data['judul'],
                'deskripsi' => $data['deskripsi'] ?? null,
                'mulai_at' => $data['mulai_at'] ?? null,
                'akhir_at' => $data['akhir_at'] ?? null,
                'is_active' => !empty($data['is_active']),
                'created_by' => Auth::id(),
            ]);

            if (!empty($data['pertanyaan']) && is_array($data['pertanyaan'])) {
                $urutan = 1;

                foreach ($data['pertanyaan'] as $p) {
                    if (empty($p['teks'])) {
                        continue;
                    }

                    $pertanyaan = SurveiPertanyaan::create([
                        'survei_id' => $survei->id,
                        'pertanyaan' => $p['teks'],
                        'tipe' => $p['tipe'] ?? 'text',
                        'urutan' => $urutan++,
                    ]);

                    if (
                        in_array($pertanyaan->tipe, ['radio', 'checkbox', 'dropdown', 'skala'], true)
                        && !empty($p['opsi'])
                    ) {
                        foreach ($p['opsi'] as $opsiText) {
                            if (trim((string) $opsiText) !== '') {
                                SurveiOpsi::create([
                                    'pertanyaan_id' => $pertanyaan->id,
                                    'opsi' => trim((string) $opsiText),
                                ]);
                            }
                        }
                    }
                }
            }
        });

        return redirect()
            ->route('admin.survei.index')
            ->with('ok', 'Survei berhasil dibuat lengkap dengan pertanyaan dan opsi.')
            ->with('flash_type', 'created');
    }

    /** ===========================================================
     *  SHOW
     *  =========================================================== */
    public function show(Survei $survei)
    {
        $survei->load(['pertanyaan.opsi']);

        return view('admin.survei.show', compact('survei'));
    }

    /** ===========================================================
     *  EDIT / UPDATE
     *  =========================================================== */
    public function edit(Survei $survei)
    {
        $pertanyaan = $survei->pertanyaan()
            ->with('opsi')
            ->orderBy('urutan')
            ->get();

        return view('admin.survei.edit', compact('survei', 'pertanyaan'));
    }

    public function update(Request $request, Survei $survei)
    {
        $request->validate([
            'judul' => 'required|string|max:150',
            'deskripsi' => 'nullable|string',
            'mulai_at' => 'nullable|date',
            'akhir_at' => 'nullable|date|after_or_equal:mulai_at',
        ]);

        $survei->update([
            'judul' => $request->judul,
            'deskripsi' => $request->deskripsi,
            'mulai_at' => $request->mulai_at,
            'akhir_at' => $request->akhir_at,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('admin.survei.index')
            ->with('ok', 'Perubahan survei berhasil disimpan.')
            ->with('flash_type', 'updated');
    }

    /** ===========================================================
     *  DESTROY
     *  =========================================================== */
    public function destroy(Survei $survei)
    {
        DB::transaction(function () use ($survei) {
            $pertanyaanIds = $survei->pertanyaan()->pluck('id');

            if ($pertanyaanIds->isNotEmpty()) {
                SurveiOpsi::whereIn('pertanyaan_id', $pertanyaanIds)->delete();
            }

            SurveiRespon::where('survei_id', $survei->id)->delete();
            SurveiPertanyaan::where('survei_id', $survei->id)->delete();

            $survei->delete();
        });

        return redirect()
            ->route('admin.survei.index')
            ->with('ok', 'Survei berhasil dihapus.')
            ->with('flash_type', 'deleted');
    }

    /** ===========================================================
     *  EXPORT EXCEL
     *  =========================================================== */
    public function exportExcel(Survei $survei)
    {
        $respon = SurveiRespon::with('ortu')
            ->where('survei_id', $survei->id)
            ->get();

        $export = new \App\Exports\SurveiExport($survei, $respon);
        $filePath = $export->generateFile();

        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    /** ===========================================================
     *  EXPORT PDF
     *  =========================================================== */
    public function exportPdf(Survei $survei)
    {
        $respon = SurveiRespon::with('ortu')
            ->where('survei_id', $survei->id)
            ->get();

        $pdf = Pdf::loadView('admin.survei.pdf', compact('survei', 'respon'))
            ->setPaper('a4', 'landscape');

        return $pdf->download('hasil-survei-' . \Illuminate\Support\Str::slug($survei->judul) . '.pdf');
    }

    /** ===========================================================
     *  PERTANYAAN
     *  =========================================================== */
    public function storePertanyaan(Request $request, $survei_id)
    {
        $request->validate([
            'pertanyaan' => 'required|string',
            'tipe' => 'required|in:text,textarea,radio,checkbox,dropdown,skala',
        ]);

        $urutan = SurveiPertanyaan::where('survei_id', $survei_id)->max('urutan') + 1;

        SurveiPertanyaan::create([
            'survei_id' => $survei_id,
            'pertanyaan' => $request->pertanyaan,
            'tipe' => $request->tipe,
            'urutan' => $urutan,
        ]);

        return back()->with('ok', 'Pertanyaan berhasil ditambahkan.');
    }

    public function updatePertanyaan(Request $request, $id)
    {
        $data = $request->validate([
            'pertanyaan' => 'required|string|max:255',
            'tipe' => 'required|string',
        ]);

        $pertanyaan = SurveiPertanyaan::findOrFail($id);
        $pertanyaan->update($data);

        return back()->with('ok', 'Pertanyaan berhasil diperbarui.');
    }

    public function destroyPertanyaan(SurveiPertanyaan $pertanyaan)
    {
        DB::transaction(function () use ($pertanyaan) {
            SurveiOpsi::where('pertanyaan_id', $pertanyaan->id)->delete();
            $pertanyaan->delete();
        });

        return back()->with('ok', 'Pertanyaan dihapus.');
    }

    public function reorderPertanyaan(Request $request, Survei $survei)
    {
        $pertanyaan = $survei->pertanyaan()->orderBy('urutan')->get();
        $target = $pertanyaan->firstWhere('id', $request->id);

        if (!$target) {
            return back();
        }

        if ($request->direction === 'up') {
            $swap = $pertanyaan->where('urutan', '<', $target->urutan)->sortByDesc('urutan')->first();
        } else {
            $swap = $pertanyaan->where('urutan', '>', $target->urutan)->sortBy('urutan')->first();
        }

        if ($swap) {
            $temp = $target->urutan;
            $target->update(['urutan' => $swap->urutan]);
            $swap->update(['urutan' => $temp]);
        }

        return back()->with('ok', 'Urutan pertanyaan diperbarui.');
    }

    /** ===========================================================
     *  OPSI
     *  =========================================================== */
    public function getOpsi($id)
    {
        $pertanyaan = SurveiPertanyaan::findOrFail($id);

        return response()->json($pertanyaan->opsi);
    }

    public function updateOpsi(Request $request, $id)
    {
        $opsi = SurveiOpsi::findOrFail($id);

        $opsi->update([
            'opsi' => $request->opsi,
        ]);

        return response()->json(['ok' => true]);
    }

    public function storeOpsi(Request $request, $pertanyaanId)
    {
        $request->validate([
            'opsi' => 'required|string|max:255',
        ]);

        SurveiOpsi::create([
            'pertanyaan_id' => $pertanyaanId,
            'opsi' => $request->opsi,
        ]);

        return back()->with('ok', 'Opsi jawaban berhasil ditambahkan.');
    }

    public function destroyOpsi($opsiId)
    {
        $opsi = SurveiOpsi::findOrFail($opsiId);
        $opsi->delete();

        return back()->with('ok', 'Opsi jawaban dihapus.');
    }

    /** ===========================================================
     *  HASIL
     *  =========================================================== */
    public function hasil(Survei $survei)
    {
        $survei->load('pertanyaan');

        $respon = SurveiRespon::where('survei_id', $survei->id)
            ->with('ortu')
            ->get();

        $jawabanList = $respon->map(fn($r) => json_decode($r->jawaban, true));

        return view('admin.survei.hasil', compact('survei', 'respon', 'jawabanList'));
    }
}