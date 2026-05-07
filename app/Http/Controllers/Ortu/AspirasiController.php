<?php

namespace App\Http\Controllers\Ortu;

use App\Http\Controllers\Controller;
use App\Models\Survei;
use App\Models\SurveiRespon;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AspirasiController extends Controller
{
    public function index()
    {
        Carbon::setLocale('id');

        $userId = Auth::id();
        $today = now('Asia/Jakarta');

        $survei = Survei::query()
            ->withCount('pertanyaan')
            ->with([
                'respon' => function ($q) use ($userId) {
                    $q->where('ortu_user_id', $userId)->latest();
                }
            ])
            ->where('is_active', true)
            ->where(function ($q) use ($today) {
                $q->whereNull('mulai_at')
                    ->orWhere('mulai_at', '<=', $today);
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('akhir_at')
                    ->orWhere('akhir_at', '>=', $today);
            })
            ->latest('mulai_at')
            ->latest('id')
            ->get()
            ->map(function ($item) {
                $respon = $item->respon->first();

                $item->sudah_isi = (bool) $respon;
                $item->tanggal_isi = $respon?->created_at;
                $item->mulai_at_parsed = $this->parseDate($item->mulai_at);
                $item->akhir_at_parsed = $this->parseDate($item->akhir_at);

                return $item;
            });

        return view('ortu.aspirasi.index', [
            'survei' => $survei,
            'today' => $today,
        ]);
    }

    public function isi($id)
    {
        Carbon::setLocale('id');

        $survei = Survei::query()
            ->with([
                'pertanyaan' => function ($q) {
                    $q->orderBy('urutan');
                },
                'pertanyaan.opsi'
            ])
            ->findOrFail($id);

        $this->abortIfNotAvailable($survei);

        $sudahIsi = SurveiRespon::query()
            ->where('survei_id', $id)
            ->where('ortu_user_id', Auth::id())
            ->exists();

        if ($sudahIsi) {
            return redirect()
                ->route('ortu.aspirasi.riwayat')
                ->with('info', 'Anda sudah pernah mengisi survei ini.');
        }

        $survei->mulai_at_parsed = $this->parseDate($survei->mulai_at);
        $survei->akhir_at_parsed = $this->parseDate($survei->akhir_at);

        return view('ortu.aspirasi.isi', [
            'survei' => $survei,
        ]);
    }

    public function kirim(Request $request, $id)
    {
        $survei = Survei::query()
            ->with([
                'pertanyaan' => function ($q) {
                    $q->orderBy('urutan');
                },
                'pertanyaan.opsi'
            ])
            ->findOrFail($id);

        $this->abortIfNotAvailable($survei);

        $sudahIsi = SurveiRespon::query()
            ->where('survei_id', $id)
            ->where('ortu_user_id', Auth::id())
            ->exists();

        if ($sudahIsi) {
            return redirect()
                ->route('ortu.aspirasi.riwayat')
                ->with('info', 'Anda sudah pernah mengisi survei ini.');
        }

        $jawabanInput = $request->input('jawaban', []);

        if (!is_array($jawabanInput) || empty($jawabanInput)) {
            return back()
                ->withInput()
                ->with('info', 'Mohon isi minimal satu pertanyaan sebelum dikirim.');
        }

        $jawabanFinal = [];

        foreach ($survei->pertanyaan as $pertanyaan) {
            $qid = $pertanyaan->id;
            $tipe = $pertanyaan->tipe ?? 'text';
            $value = $jawabanInput[$qid] ?? null;

            if ($tipe === 'checkbox') {
                $value = is_array($value)
                    ? array_values(array_filter($value, fn($v) => filled($v)))
                    : [];
            } elseif ($tipe === 'skala') {
                $value = filled($value) && in_array((string) $value, ['1', '2', '3', '4', '5'], true)
                    ? (string) $value
                    : null;
            } else {
                $value = is_string($value) ? trim($value) : $value;
                $value = filled($value) ? $value : null;
            }

            if (is_array($value)) {
                if (!empty($value)) {
                    $jawabanFinal[$qid] = $value;
                }
            } else {
                if ($value !== null && $value !== '') {
                    $jawabanFinal[$qid] = $value;
                }
            }
        }

        if (empty($jawabanFinal)) {
            return back()
                ->withInput()
                ->with('info', 'Mohon isi minimal satu pertanyaan sebelum dikirim.');
        }

        DB::transaction(function () use ($survei, $jawabanFinal) {
            SurveiRespon::create([
                'survei_id' => $survei->id,
                'ortu_user_id' => Auth::id(),
                'jawaban' => $jawabanFinal,
            ]);
        });

        return redirect()
            ->route('ortu.aspirasi.riwayat')
            ->with('ok', 'Terima kasih! Jawaban Anda telah berhasil dikirim.');
    }

    public function riwayat()
    {
        Carbon::setLocale('id');

        $userId = Auth::id();

        $riwayat = Survei::query()
            ->whereHas('respon', function ($q) use ($userId) {
                $q->where('ortu_user_id', $userId);
            })
            ->with([
                'respon' => function ($q) use ($userId) {
                    $q->where('ortu_user_id', $userId)->latest();
                }
            ])
            ->withCount('pertanyaan')
            ->latest('id')
            ->get()
            ->map(function ($item) {
                $item->mulai_at_parsed = $this->parseDate($item->mulai_at);
                $item->akhir_at_parsed = $this->parseDate($item->akhir_at);

                return $item;
            });

        return view('ortu.aspirasi.riwayat', [
            'riwayat' => $riwayat,
            'today' => now('Asia/Jakarta'),
        ]);
    }

    public function showRiwayat($id)
    {
        Carbon::setLocale('id');

        $survei = Survei::query()
            ->with([
                'pertanyaan' => function ($q) {
                    $q->orderBy('urutan');
                },
                'pertanyaan.opsi'
            ])
            ->findOrFail($id);

        $survei->mulai_at_parsed = $this->parseDate($survei->mulai_at);
        $survei->akhir_at_parsed = $this->parseDate($survei->akhir_at);

        $respon = SurveiRespon::query()
            ->where('survei_id', $id)
            ->where('ortu_user_id', Auth::id())
            ->latest()
            ->firstOrFail();

        $jawaban = is_array($respon->jawaban) ? $respon->jawaban : [];

        return view('ortu.aspirasi.show-riwayat', [
            'survei' => $survei,
            'respon' => $respon,
            'jawaban' => $jawaban,
        ]);
    }

    private function abortIfNotAvailable(Survei $survei): void
    {
        $now = now('Asia/Jakarta');

        $mulaiAt = $this->parseDate($survei->mulai_at);
        $akhirAt = $this->parseDate($survei->akhir_at);

        $isAvailable =
            (bool) $survei->is_active &&
            (!$mulaiAt || $mulaiAt->lte($now)) &&
            (!$akhirAt || $akhirAt->gte($now));

        abort_unless($isAvailable, 404);
    }

    private function parseDate($value): ?Carbon
    {
        if (!$value) {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value->timezone('Asia/Jakarta');
        }

        try {
            return Carbon::parse($value)->timezone('Asia/Jakarta');
        } catch (\Throwable $e) {
            return null;
        }
    }
}