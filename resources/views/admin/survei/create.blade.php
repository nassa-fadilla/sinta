@extends('admin.layout')
@section('title', 'Buat Survei Baru')

@section('content')
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
                                Buat Survei Baru
                            </h1>
                            <p class="mt-1 text-sm text-slate-500">
                                Susun informasi survei dan daftar pertanyaan untuk orang tua.
                            </p>
                        </div>
                    </div>

                    <a href="{{ route('admin.survei.index') }}"
                        class="inline-flex items-center gap-2 self-start rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 hover:shadow-md">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="1.9">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                        <span>Kembali</span>
                    </a>
                </div>
            </div>

            <div class="p-5 md:p-6 space-y-6">
                @if ($errors->any())
                    <div class="rounded-[1.5rem] border border-rose-200 bg-rose-50/80 px-4 py-4 shadow-sm">
                        <p class="text-sm font-semibold text-rose-700">Periksa kembali isian berikut:</p>
                        <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-rose-700">
                            @foreach ($errors->all() as $e)
                                <li>{{ $e }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- INFORMASI SURVEI --}}
                <div class="rounded-[1.5rem] border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <h2 class="text-sm font-semibold text-slate-800">Informasi Survei</h2>
                        <p class="mt-1 text-xs text-slate-500">
                            Lengkapi judul, deskripsi, periode pelaksanaan, dan status aktif survei.
                        </p>
                    </div>

                    <div class="p-5 md:p-6 space-y-6">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">
                                Judul Survei <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" id="judul"
                                class="w-full rounded-2xl border border-slate-200 bg-slate-50/60 px-3 py-2.5 text-sm shadow-sm transition focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-100"
                                required>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Deskripsi</label>
                            <textarea id="deskripsi" rows="3"
                                class="w-full rounded-2xl border border-slate-200 bg-slate-50/60 px-3 py-2.5 text-sm shadow-sm transition focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-100"></textarea>
                        </div>

                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Mulai</label>
                                <input type="datetime-local" id="mulai_at"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50/60 px-3 py-2.5 text-sm shadow-sm transition focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-100">
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Akhir</label>
                                <input type="datetime-local" id="akhir_at"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50/60 px-3 py-2.5 text-sm shadow-sm transition focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-100">
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="is_active" checked
                                class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                            <label for="is_active" class="text-sm font-medium text-slate-700">
                                Aktifkan survei
                            </label>
                        </div>
                    </div>
                </div>

                {{-- DAFTAR PERTANYAAN --}}
                <div class="rounded-[1.5rem] border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 class="text-sm font-semibold text-slate-800">Daftar Pertanyaan</h2>
                                <p class="mt-1 text-xs text-slate-500">
                                    Susun urutan pertanyaan, tipe jawaban, dan opsi pilihan untuk orang tua.
                                </p>
                            </div>

                            <span
                                class="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                                Builder Survei
                            </span>
                        </div>
                    </div>

                    <form id="formSurvei" method="POST" action="{{ route('admin.survei.store') }}"
                        class="p-5 md:p-6 space-y-6">
                        @csrf
                        <input type="hidden" name="payload" id="payload">

                        <div id="questionList" class="space-y-5"></div>

                        <div class="flex justify-center">
                            <button type="button" id="addQuestion"
                                class="inline-flex items-center justify-center rounded-2xl border border-blue-200 bg-blue-50 px-5 py-2.5 text-sm font-medium text-blue-700 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:bg-blue-100">
                                + Tambah Pertanyaan
                            </button>
                        </div>

                        <p id="builderError"
                            class="hidden rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-600">
                            Periksa lagi: pertanyaan bertipe pilihan <strong>harus memiliki minimal 1 opsi</strong>, dan
                            pertanyaan tidak boleh kosong.
                        </p>

                        <div class="flex items-center justify-end gap-3 border-t border-slate-200 pt-5">
                            <a href="{{ route('admin.survei.index') }}"
                                class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700">
                                Batal
                            </a>

                            <button type="submit"
                                class="inline-flex items-center justify-center rounded-2xl bg-blue-500 px-6 py-2.5 text-sm font-medium text-white shadow-sm transition duration-300 hover:-translate-y-0.5 hover:bg-blue-600 hover:shadow-md">
                                Simpan Survei
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <style>
                #questionList .sortable-ghost {
                    opacity: 0.6;
                    background: rgba(219, 234, 254, 0.8);
                    border-color: rgba(96, 165, 250, 0.5);
                }

                #questionList .sortable-chosen {
                    cursor: grabbing !important;
                }
            </style>
        </section>
    </div>

    {{-- TEMPLATE PERTANYAAN --}}
    <template id="questionTemplate">
        <div class="q-card relative rounded-[1.5rem] border border-slate-200 bg-slate-50/70 p-5 shadow-sm transition duration-300 hover:shadow-md cursor-grab active:cursor-grabbing"
            draggable="true">

            {{-- tombol hapus --}}
            <button type="button"
                class="remove-question absolute right-4 top-4 inline-flex h-8 w-8 items-center justify-center rounded-full bg-white text-slate-400 shadow-sm ring-1 ring-slate-200 transition hover:bg-rose-50 hover:text-rose-600 hover:ring-rose-200"
                title="Hapus pertanyaan">
                ✕
            </button>

            <div class="space-y-4">
                <div class="flex items-center gap-2 text-[11px] text-slate-500">
                    <span
                        class="q-number inline-flex items-center rounded-full bg-blue-50 px-2.5 py-1 font-semibold text-blue-700 ring-1 ring-blue-200">
                        Pertanyaan
                    </span>
                    <span class="q-error hidden rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-rose-600">
                        Lengkapi pertanyaan/opsi
                    </span>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Teks Pertanyaan</label>
                    <input type="text"
                        class="q-text w-full rounded-2xl border border-slate-200 bg-white px-3 py-2.5 text-sm shadow-sm transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        required>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Tipe Pertanyaan</label>
                        <select
                            class="q-type w-full rounded-2xl border border-slate-200 bg-white px-3 py-2.5 text-sm shadow-sm transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                            <option value="text">Teks</option>
                            <option value="textarea">Teks Panjang</option>
                            <option value="radio">Pilihan Ganda</option>
                            <option value="checkbox">Checkbox</option>
                            <option value="dropdown">Dropdown</option>
                            <option value="skala">Skala (1–5)</option>
                        </select>
                        <p class="q-hint mt-1 text-xs text-slate-500"></p>
                    </div>

                    <div class="opsi-container hidden">
                        <label class="mb-1 block text-sm font-medium text-slate-700">Opsi Jawaban</label>
                        <div class="opsi-list space-y-2"></div>

                        <div class="mt-2 flex items-center gap-2">
                            <button type="button"
                                class="add-option rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700 transition hover:bg-blue-100">
                                + Tambah Opsi
                            </button>

                            <button type="button"
                                class="seed-scale hidden rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700 transition hover:bg-blue-100">
                                Isi Otomatis 1–5
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script>
        const questionList = document.getElementById('questionList');
        const template = document.getElementById('questionTemplate').content;
        const addButton = document.getElementById('addQuestion');
        const builderError = document.getElementById('builderError');

        // Default mulai_at ke waktu sekarang
        const now = new Date();
        const pad = n => String(n).padStart(2, '0');
        const localNow = `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}T${pad(now.getHours())}:${pad(now.getMinutes())}`;
        document.getElementById('mulai_at').value = localNow;

        addButton.addEventListener('click', () => {
            const node = newQuestion();
            questionList.appendChild(node);
            renumber();
            window.scrollTo({
                top: document.body.scrollHeight,
                behavior: 'smooth'
            });
        });

        function newQuestion() {
            const clone = document.importNode(template, true);
            const card = clone.querySelector('.q-card');
            const type = clone.querySelector('.q-type');
            const opsiContainer = clone.querySelector('.opsi-container');
            const opsiList = clone.querySelector('.opsi-list');
            const hint = clone.querySelector('.q-hint');
            const seedScaleBtn = clone.querySelector('.seed-scale');

            type.addEventListener('change', () => {
                const val = type.value;
                opsiList.innerHTML = '';
                if (['radio', 'checkbox', 'dropdown', 'skala'].includes(val)) {
                    opsiContainer.classList.remove('hidden');

                    if (val === 'skala') {
                        seedScaleBtn.classList.remove('hidden');
                        seedScale(opsiList);
                        hint.textContent = 'Skala 1–5 diisi otomatis. Anda bisa mengubah labelnya.';
                    } else {
                        seedScaleBtn.classList.add('hidden');
                        addOptionInput(opsiList);
                        addOptionInput(opsiList);
                        hint.textContent = 'Tambahkan minimal 1 opsi.';
                    }
                } else {
                    hint.textContent = '';
                    seedScaleBtn.classList.add('hidden');
                    opsiContainer.classList.add('hidden');
                }
            });

            clone.querySelector('.add-option').addEventListener('click', () => addOptionInput(opsiList));

            seedScaleBtn.addEventListener('click', () => seedScale(opsiList));

            clone.querySelector('.remove-question').addEventListener('click', () => {
                card.remove();
                renumber();
            });

            return clone;
        }

        function addOptionInput(opsiList, value = '') {
            const div = document.createElement('div');
            div.className = 'flex items-center gap-2';
            div.innerHTML = `
                                        <input type="text" value="${value.replace(/"/g, '&quot;')}"
                                            class="q-option flex-1 rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-sm focus:border-blue-500 focus:ring focus:ring-blue-100"
                                            placeholder="Tulis opsi..." required>
                                        <button type="button" class="remove-option text-xs text-red-500 hover:text-red-700">✕</button>
                                    `;
            opsiList.appendChild(div);
            div.querySelector('.remove-option').addEventListener('click', () => div.remove());
        }

        function seedScale(opsiList) {
            opsiList.innerHTML = '';
            [
                '1 – Sangat Tidak Setuju',
                '2 – Tidak Setuju',
                '3 – Netral',
                '4 – Setuju',
                '5 – Sangat Setuju'
            ].forEach(v => addOptionInput(opsiList, v));
        }

        questionList.addEventListener('click', e => {
            if (e.target.classList.contains('add-option')) {
                const opsiList = e.target.closest('.opsi-container').querySelector('.opsi-list');
                addOptionInput(opsiList);
            }
        });

        new Sortable(questionList, {
            animation: 150,
            ghostClass: 'bg-blue-50',
            handle: '.q-card',
            onEnd: renumber,
        });

        function renumber() {
            [...questionList.querySelectorAll('.q-number')]
                .forEach((el, i) => el.textContent = `Pertanyaan ${i + 1}`);
        }

        document.getElementById('formSurvei').addEventListener('submit', e => {
            builderError.classList.add('hidden');
            let hasError = false;

            const pertanyaan = [];
            questionList.querySelectorAll('.q-card').forEach(card => {
                const textInput = card.querySelector('.q-text');
                const typeSel = card.querySelector('.q-type');
                const errBadge = card.querySelector('.q-error');

                errBadge.classList.add('hidden');
                textInput.classList.remove('border-red-400', 'ring-red-200');

                const teks = (textInput.value || '').trim();
                const tipe = typeSel.value;

                let opsi = [];
                if (['radio', 'checkbox', 'dropdown', 'skala'].includes(tipe)) {
                    opsi = [...card.querySelectorAll('.q-option')]
                        .map(i => i.value.trim())
                        .filter(Boolean);
                    if (opsi.length === 0) {
                        hasError = true;
                        errBadge.classList.remove('hidden');
                        card.querySelector('.opsi-list')
                            .classList.add('ring-1', 'ring-red-200', 'rounded-lg');
                    } else {
                        card.querySelector('.opsi-list')
                            .classList.remove('ring-1', 'ring-red-200');
                    }
                }

                if (!teks) {
                    hasError = true;
                    errBadge.classList.remove('hidden');
                    textInput.classList.add('border-red-400', 'ring-red-200');
                }

                if (teks) {
                    pertanyaan.push({
                        teks,
                        tipe,
                        opsi
                    });
                }
            });

            if (pertanyaan.length === 0) {
                hasError = true;
                builderError.classList.remove('hidden');
            }

            if (hasError) {
                e.preventDefault();
                builderError.classList.remove('hidden');
                return;
            }

            const payload = {
                judul: document.getElementById('judul').value.trim(),
                deskripsi: document.getElementById('deskripsi').value.trim(),
                mulai_at: document.getElementById('mulai_at').value,
                akhir_at: document.getElementById('akhir_at').value,
                is_active: document.getElementById('is_active').checked,
                pertanyaan
            };

            document.getElementById('payload').value = JSON.stringify(payload);
        });
    </script>
@endsection