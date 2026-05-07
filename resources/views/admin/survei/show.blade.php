@extends('admin.layout')
@section('title', 'Detail Survei')

@section('content')
    @php
        use App\Models\SurveiRespon;

        $respon = SurveiRespon::with('ortu')
            ->where('survei_id', $survei->id)
            ->orderByDesc('created_at')
            ->get();

        $decodeJawaban = function ($raw) {
            if (is_array($raw))
                return $raw;
            if (is_string($raw)) {
                $d = json_decode($raw, true);
                return is_array($d) ? $d : [];
            }
            return [];
        };

        $ringkasan = [];
        foreach ($survei->pertanyaan as $p) {
            $ringkasan[$p->id] = ['total' => 0, 'opsi' => [], 'tipe' => $p->tipe];
        }

        foreach ($respon as $r) {
            $decoded = $decodeJawaban($r->jawaban);

            if (array_is_list($decoded)) {
                $decoded = collect($survei->pertanyaan)
                    ->mapWithKeys(fn($p, $i) => [$p->id => $decoded[$i] ?? null])
                    ->toArray();
            }

            foreach ($decoded as $pid => $val) {
                if (!isset($ringkasan[$pid]))
                    continue;

                $values = is_array($val) ? $val : [$val];
                $ringkasan[$pid]['total']++;

                $opsiPertanyaan = $survei->pertanyaan->firstWhere('id', $pid)?->opsi?->pluck('opsi')->values()->toArray() ?? [];

                foreach ($values as $v) {
                    $key = trim((string) $v);

                    if (is_numeric($key) && isset($opsiPertanyaan[$key - 1])) {
                        $key = $opsiPertanyaan[$key - 1];
                    }

                    $ringkasan[$pid]['opsi'][$key] = ($ringkasan[$pid]['opsi'][$key] ?? 0) + 1;
                }
            }
        }

        $pct = fn($p, $t) => $t > 0 ? round(($p / $t) * 100) : 0;

        $labelOpsi = [];
        foreach ($survei->pertanyaan as $p) {
            $labelOpsi[$p->id] = $p->opsi->pluck('opsi')->toArray();
        }
    @endphp

    <div class="space-y-6">
        <section
            class="overflow-hidden rounded-[1.5rem] border border-slate-200/70 bg-white shadow-[0_20px_60px_rgba(15,23,42,0.08)] transition duration-300 hover:shadow-[0_24px_70px_rgba(15,23,42,0.10)]">

            {{-- HEADER --}}
            <div class="border-b border-slate-200 px-5 py-5 md:px-6">
                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div class="flex items-start gap-4">
                        <div
                            class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-blue-500 text-white shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 5h6m-3-2.25A1.75 1.75 0 0 1 13.75 4.5h.75A2.75 2.75 0 0 1 17.25 7.25v9.5A2.75 2.75 0 0 1 14.5 19.5h-5A2.75 2.75 0 0 1 6.75 16.75v-9.5A2.75 2.75 0 0 1 9.5 4.5h.75A1.75 1.75 0 0 1 12 2.75z" />
                            </svg>
                        </div>

                        <div>
                            <h1 class="text-2xl font-semibold tracking-tight text-slate-800">
                                {{ $survei->judul }}
                            </h1>
                            <p class="mt-1 text-sm text-slate-500">
                                Detail survei dan ringkasan jawaban orang tua.
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('admin.survei.index') }}"
                            class="inline-flex items-center gap-2 self-start rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 hover:shadow-md">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="1.9">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                            </svg>
                            <span>Kembali</span>
                        </a>

                        <form action="{{ route('admin.survei.destroy', $survei->id) }}" method="POST"
                            onsubmit="return confirm('Yakin ingin menghapus survei ini? Semua pertanyaan, opsi, dan respon yang terkait juga akan dihapus.');">
                            @csrf
                            @method('DELETE')

                            <button type="submit"
                                class="inline-flex items-center gap-2 self-start rounded-2xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-medium text-rose-700 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:bg-rose-100 hover:shadow-md">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M6 7h12m-9 0V5a1 1 0 011-1h4a1 1 0 011 1v2m-7 4v6m4-6v6m-7-10 1 13h10l1-13" />
                                </svg>
                                <span>Hapus</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="p-5 md:p-6 space-y-6">
                {{-- INFORMASI SURVEI --}}
                <div class="rounded-[1.5rem] border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 class="text-sm font-semibold text-slate-800">Informasi Survei</h2>
                                <p class="mt-1 text-xs text-slate-500">
                                    Periode pelaksanaan, status survei, dan jumlah respon.
                                </p>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    class="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                                    {{ $respon->count() }} respon
                                </span>

                                @if($survei->is_active)
                                    <span
                                        class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                                        Aktif
                                    </span>
                                @else
                                    <span
                                        class="inline-flex items-center rounded-full border border-slate-200 bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                                        Nonaktif
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="p-5 md:p-6 space-y-5">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div class="rounded-2xl border border-blue-200 bg-blue-50 px-4 py-3">
                                <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-blue-500">
                                    Mulai
                                </p>
                                <p class="mt-2 text-sm font-semibold text-slate-800">
                                    {{ $survei->mulai_at ? date('d M Y H:i', strtotime($survei->mulai_at)) : '-' }}
                                </p>
                            </div>

                            <div class="rounded-2xl border border-violet-200 bg-violet-50 px-4 py-3">
                                <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-violet-500">
                                    Akhir
                                </p>
                                <p class="mt-2 text-sm font-semibold text-slate-800">
                                    {{ $survei->akhir_at ? date('d M Y H:i', strtotime($survei->akhir_at)) : '-' }}
                                </p>
                            </div>

                            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3">
                                <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-emerald-500">
                                    Total Pertanyaan
                                </p>
                                <p class="mt-2 text-sm font-semibold text-slate-800">
                                    {{ $survei->pertanyaan->count() }} pertanyaan
                                </p>
                            </div>
                        </div>

                        <div>
                            <h3 class="mb-1.5 text-sm font-semibold text-slate-800">Deskripsi</h3>
                            <p class="text-sm leading-relaxed text-slate-700">
                                {{ $survei->deskripsi ?: '-' }}
                            </p>
                        </div>
                    </div>
                </div>

                {{-- RINGKASAN JAWABAN --}}
                <div class="rounded-[1.5rem] border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 class="text-sm font-semibold text-slate-800">Ringkasan Jawaban</h2>
                                <p class="mt-1 text-xs text-slate-500">
                                    Distribusi jawaban berdasarkan setiap pertanyaan.
                                </p>
                            </div>

                            <span
                                class="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                                Total respon: {{ $respon->count() }}
                            </span>
                        </div>
                    </div>

                    <div class="p-5 md:p-6 space-y-5">
                        @forelse($survei->pertanyaan as $i => $p)
                            @php
                                $meta = $ringkasan[$p->id] ?? ['total' => 0, 'opsi' => [], 'tipe' => $p->tipe];
                                $total = $meta['total'];
                                $opsiCount = $meta['opsi'];
                                $orderedKeys = $labelOpsi[$p->id] ?? array_keys($opsiCount);
                            @endphp

                            <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50/70 p-5 shadow-sm">
                                <div class="mb-4 flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="inline-flex h-8 min-w-[32px] items-center justify-center rounded-full bg-blue-50 px-2 text-xs font-semibold text-blue-700 ring-1 ring-blue-200">
                                                {{ $i + 1 }}
                                            </span>
                                            <h3 class="text-sm font-semibold text-slate-800">
                                                Pertanyaan {{ $i + 1 }}
                                            </h3>
                                        </div>

                                        <p class="mt-2 text-sm text-slate-700">
                                            {{ $p->pertanyaan }}
                                        </p>
                                    </div>

                                    <span
                                        class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-slate-600 ring-1 ring-slate-200">
                                        {{ strtoupper($p->tipe) }}
                                    </span>
                                </div>

                                @if(in_array($p->tipe, ['radio', 'checkbox', 'dropdown', 'skala']))
                                    @if($total === 0)
                                        <p class="text-xs italic text-slate-500">Belum ada jawaban.</p>
                                    @else
                                        <div class="space-y-3">
                                            @foreach($orderedKeys as $label)
                                                @php
                                                    $count = $opsiCount[$label] ?? 0;
                                                    $percent = $pct($count, $total);
                                                @endphp
                                                <div>
                                                    <div class="mb-1 flex items-center justify-between text-xs text-slate-700">
                                                        <span>{{ $label }}</span>
                                                        <span class="tabular-nums text-slate-500">
                                                            {{ $count }} ({{ $percent }}%)
                                                        </span>
                                                    </div>
                                                    <div class="h-2 w-full overflow-hidden rounded-full bg-slate-200">
                                                        <div class="h-full rounded-full bg-blue-500 transition-all duration-300"
                                                            style="width: {{ $percent }}%"></div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                @else
                                    @php
                                        $contoh = collect($respon)
                                            ->map(function ($r) use ($decodeJawaban, $p, $i) {
                                                $decoded = $decodeJawaban($r->jawaban);
                                                $val = $decoded[$p->id] ?? ($decoded[$i] ?? null);
                                                return is_array($val) ? implode(', ', $val) : $val;
                                            })
                                            ->filter()
                                            ->take(3);
                                    @endphp

                                    @if($contoh->isEmpty())
                                        <p class="text-xs italic text-slate-500">Belum ada jawaban.</p>
                                    @else
                                        <p class="mb-2 text-xs font-medium text-slate-500">Contoh jawaban:</p>
                                        <ul class="ml-5 list-disc space-y-1 text-sm text-slate-700">
                                            @foreach($contoh as $c)
                                                <li>{{ $c }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                @endif
                            </div>
                        @empty
                            <p class="py-6 text-center text-sm text-slate-500">
                                Belum ada pertanyaan.
                            </p>
                        @endforelse
                    </div>
                </div>

                {{-- DAFTAR RESPON DETAIL --}}
                <div class="rounded-[1.5rem] border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <h2 class="text-sm font-semibold text-slate-800">Daftar Respon</h2>
                                <p class="mt-1 text-xs text-slate-500">
                                    Matriks jawaban tiap orang tua untuk setiap pertanyaan.
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('admin.survei.export.excel', $survei->id) }}"
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white shadow-sm transition hover:bg-emerald-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M4 4h16v16H4zM9 9l6 6m0-6l-6 6" />
                                    </svg>
                                    <span>Export Excel</span>
                                </a>

                                <a href="{{ route('admin.survei.export.pdf', $survei->id) }}"
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-medium text-white shadow-sm transition hover:bg-rose-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M7 4h10v16H7zM10 8h4m-4 4h4m-4 4h2" />
                                    </svg>
                                    <span>Export PDF</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto p-4 md:p-6">
                        <table class="min-w-[1000px] w-full overflow-hidden rounded-xl border border-slate-200 text-sm">
                            <thead>
                                <tr class="bg-slate-100 text-slate-700">
                                    <th
                                        class="w-[40px] border-b border-slate-200 px-3 py-2 text-center text-xs font-semibold">
                                        No
                                    </th>
                                    <th
                                        class="min-w-[320px] border-b border-slate-200 px-4 py-2 text-left text-xs font-semibold">
                                        Pertanyaan
                                    </th>
                                    @foreach($respon as $r)
                                        <th class="border-b border-slate-200 px-4 py-2 text-center text-xs font-semibold">
                                            <div class="font-semibold text-slate-800">
                                                {{ $r->ortu->name ?? '-' }}
                                            </div>
                                            <div class="text-[11px] text-slate-500">
                                                {{ date('d M Y H:i', strtotime($r->created_at)) }}
                                            </div>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($survei->pertanyaan as $i => $p)
                                    <tr class="{{ $loop->even ? 'bg-slate-50/60' : 'bg-white' }}">
                                        <td class="border-t border-slate-200 px-3 py-2 text-center text-xs text-slate-600">
                                            {{ $i + 1 }}
                                        </td>
                                        <td class="border-t border-slate-200 px-4 py-3 align-top text-sm text-slate-800">
                                            {{ $p->pertanyaan }}
                                        </td>
                                        @foreach($respon as $r)
                                            @php
                                                $decoded = $decodeJawaban($r->jawaban);
                                                $val = $decoded[$p->id] ?? ($decoded[$i] ?? null);
                                                if (is_numeric($val)) {
                                                    $opsiPertanyaan = $p->opsi->pluck('opsi')->toArray();
                                                    $val = $opsiPertanyaan[$val - 1] ?? $val;
                                                }
                                                if (is_array($val))
                                                    $val = implode(', ', $val);
                                            @endphp
                                            <td
                                                class="border-t border-slate-200 px-4 py-3 align-top text-center text-xs text-slate-700">
                                                {{ $val ?: '—' }}
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </section>
    </div>
@endsection