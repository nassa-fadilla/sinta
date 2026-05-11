<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SiaClient;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WaController extends Controller
{
    protected SiaClient $sia;

    public function __construct(SiaClient $sia)
    {
        $this->sia = $sia;
    }

    /* =========================================================
     |  LIST THREAD (Riwayat Chat Admin)
     * =======================================================*/
    public function index(Request $r)
    {
        $q = trim((string) $r->query('q', ''));

        $threads = $this->buildThreadList((int) Auth::id(), $q);

        return view('admin.chat.index', [
            'threads' => $threads,
            'activeThread' => null,
            'messages' => collect(),
            'wa_target' => null,
            'q' => $q,
        ]);
    }

    /* =========================================================
     |  FORM KIRIM BARU
     * =======================================================*/
    public function create()
    {
        $ortuUsers = DB::table('users')
            ->where('role', 'ortu')
            ->whereNotNull('sia_user_id')
            ->select('id', 'name', 'sia_user_id')
            ->get();

        $parents = collect();

        foreach ($ortuUsers as $u) {
            $detail = $this->fetchSiswaDetailByNis((string) $u->sia_user_id);

            if (!$detail) {
                continue;
            }

            $photo = $this->normalizeSiswaPhotoFields($detail);

            $obj = (object) [
                'user_ortu_id' => $u->id,
                'display_name' => $u->name,

                'nis' => $detail['nis'] ?? $u->sia_user_id,
                'siswa_id' => $detail['id'] ?? null,
                'siswa_nama' => $detail['nama'] ?? '(data siswa tidak ditemukan)',

                'nama_ayah' => $detail['nama_ayah'] ?? null,
                'no_hp_ayah' => $detail['no_hp_ayah'] ?? null,
                'nama_ibu' => $detail['nama_ibu'] ?? null,
                'no_hp_ibu' => $detail['no_hp_ibu'] ?? null,

                'foto' => $photo['foto'],
                'foto_url' => $photo['foto_url'],
                'photo_url' => $photo['photo_url'],
                'avatar' => $photo['avatar'],
                'foto_siswa' => $photo['foto_siswa'],
                'foto_src' => $photo['foto_src'],
                'preview_foto' => $photo['preview_foto'],
                'student_photo_url' => $photo['foto_src'],
            ];

            $obj->wa_target = $this->pickPhone($obj->no_hp_ayah ?? null, $obj->no_hp_ibu ?? null);

            $parents->push($obj);
        }

        $parents = $parents->sortBy('siswa_nama')->values();

        Log::info('[CHAT][ADMIN] parents di create()', [
            'count' => $parents->count(),
        ]);

        return view('admin.chat.create', compact('parents'));
    }

    /* =========================================================
     |  PROSES KIRIM BARU (Admin -> Ortu)
     * =======================================================*/
    public function store(Request $request)
    {
        $data = $request->validate([
            'nis' => ['required', 'string'],
            'message' => ['required', 'string'],
        ], [], [
            'nis' => 'Siswa / Orang Tua',
            'message' => 'Pesan',
        ]);

        $nis = trim((string) $data['nis']);

        $siswa = $this->fetchSiswaDetailByNis($nis);
        if (!$siswa) {
            return back()
                ->withErrors(['nis' => 'Data siswa tidak ditemukan di SIA.'])
                ->withInput();
        }

        $userOrtu = DB::table('users')
            ->where('role', 'ortu')
            ->where('sia_user_id', $nis)
            ->select('id', 'name')
            ->first();

        if (!$userOrtu) {
            return back()
                ->withErrors(['nis' => 'Akun orang tua belum terdaftar di SINTA.'])
                ->withInput();
        }

        $target = $this->pickPhone($siswa['no_hp_ayah'] ?? null, $siswa['no_hp_ibu'] ?? null);
        if (!$target) {
            Log::warning('[CHAT][ADMIN] nomor WA kosong di store()', [
                'nis' => $nis,
                'raw' => $siswa,
            ]);

            return back()
                ->withErrors(['nis' => 'Nomor WhatsApp orang tua tidak tersedia.'])
                ->withInput();
        }

        $thread = DB::table('chat_threads')
            ->where('owner_parent_id', $userOrtu->id)
            ->where('assigned_to_user_id', Auth::id())
            ->whereIn('status', ['open', 'pending'])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->first();

        if (!$thread) {
            $threadId = DB::table('chat_threads')->insertGetId([
                'owner_parent_id' => $userOrtu->id,
                'assigned_to_user_id' => Auth::id(),
                'status' => 'open',
                'last_channel' => 'web',
                'last_message_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $threadId = (int) $thread->id;

            DB::table('chat_threads')
                ->where('id', $threadId)
                ->update([
                    'assigned_to_user_id' => Auth::id(),
                    'status' => 'open',
                    'last_channel' => 'web',
                    'last_message_at' => now(),
                    'updated_at' => now(),
                ]);
        }

        $sendResult = $this->sendToWhatsappAdmin($target, $data['message']);

        DB::table('chat_messages')->insert([
            'thread_id' => $threadId,
            'direction' => 'out',
            'channel' => 'web',
            'sender_type' => 'admin',
            'sender_id' => Auth::id(),
            'message_type' => 'teks',
            'message_status' => $sendResult['success'] ? 'terkirim' : 'gagal',
            'body' => $data['message'],
            'external_id' => $sendResult['external_id'],
            'delivered_at' => $sendResult['success'] ? now() : null,
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('admin.chat.show', $threadId)
            ->with(
                'success',
                $sendResult['success']
                ? 'Pesan terkirim & tersimpan.'
                : 'Pesan tersimpan, tetapi gagal dikirim ke WhatsApp.'
            );
    }

    /* =========================================================
     |  DETAIL THREAD
     * =======================================================*/
    public function show(Request $request, int $id)
    {
        $q = trim((string) $request->query('q', ''));

        $activeThread = DB::table('chat_threads as t')
            ->join('users as u', 'u.id', '=', 't.owner_parent_id')
            ->select(
                't.*',
                'u.name as parent_name',
                'u.sia_user_id as nis'
            )
            ->where('t.id', $id)
            ->where('t.assigned_to_user_id', Auth::id())
            ->first();

        if (!$activeThread) {
            abort(403, 'Percakapan ini tidak termasuk thread yang Anda tangani.');
        }

        DB::table('chat_messages')
            ->where('thread_id', $id)
            ->where('sender_type', 'parent')
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
                'updated_at' => now(),
            ]);

        $threads = $this->buildThreadList((int) Auth::id(), $q);

        if (!$threads->contains(fn($t) => (int) $t->id === (int) $activeThread->id)) {
            $threadTambahan = $this->buildSingleThreadItem((int) $activeThread->id, (int) Auth::id());

            if ($threadTambahan) {
                $threads = collect([$threadTambahan])
                    ->concat($threads)
                    ->unique('id')
                    ->values();
            }
        }

        $wa_target = null;

        if ($activeThread->nis) {
            $siswa = $this->fetchSiswaDetailByNis(trim((string) $activeThread->nis));

            if ($siswa) {
                $photo = $this->normalizeSiswaPhotoFields($siswa);

                $activeThread->student_name = $siswa['nama'] ?? null;
                $activeThread->nama_ayah = $siswa['nama_ayah'] ?? null;
                $activeThread->nama_ibu = $siswa['nama_ibu'] ?? null;
                $activeThread->rombel_nama = $this->extractRombelNama($siswa);

                $activeThread->foto = $photo['foto'];
                $activeThread->foto_url = $photo['foto_url'];
                $activeThread->photo_url = $photo['photo_url'];
                $activeThread->avatar = $photo['avatar'];
                $activeThread->foto_siswa = $photo['foto_siswa'];
                $activeThread->foto_src = $photo['foto_src'];
                $activeThread->preview_foto = $photo['preview_foto'];
                $activeThread->student_photo_url = $photo['foto_src'];
                $activeThread->student_photo = $photo['foto_src'];

                $parentName = $siswa['nama_ayah'] ?? $siswa['nama_ibu'] ?? $activeThread->parent_name;
                $activeThread->parent_name = $parentName ?: $activeThread->parent_name;

                $wa_target = $this->pickPhone($siswa['no_hp_ayah'] ?? null, $siswa['no_hp_ibu'] ?? null);
            } else {
                $activeThread->student_name = null;
                $activeThread->rombel_nama = null;
                $activeThread->foto = null;
                $activeThread->foto_url = null;
                $activeThread->photo_url = null;
                $activeThread->avatar = null;
                $activeThread->foto_siswa = null;
                $activeThread->foto_src = null;
                $activeThread->preview_foto = null;
                $activeThread->student_photo_url = null;
                $activeThread->student_photo = null;
            }
        } else {
            $activeThread->student_name = null;
            $activeThread->rombel_nama = null;
            $activeThread->foto = null;
            $activeThread->foto_url = null;
            $activeThread->photo_url = null;
            $activeThread->avatar = null;
            $activeThread->foto_siswa = null;
            $activeThread->foto_src = null;
            $activeThread->preview_foto = null;
            $activeThread->student_photo_url = null;
            $activeThread->student_photo = null;
        }

        $messages = DB::table('chat_messages')
            ->where('thread_id', $id)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        return view('admin.chat.index', [
            'threads' => $threads,
            'activeThread' => $activeThread,
            'messages' => $messages,
            'wa_target' => $wa_target,
            'q' => $q,
        ]);
    }

    /* =========================================================
     |  BALAS PESAN (Admin -> Ortu)
     * =======================================================*/
    public function reply(Request $request, int $id)
    {
        $data = $request->validate([
            'message' => ['required', 'string'],
        ], [], [
            'message' => 'Pesan',
        ]);

        $thread = DB::table('chat_threads as t')
            ->join('users as u', 'u.id', '=', 't.owner_parent_id')
            ->select('t.*', 'u.sia_user_id as nis')
            ->where('t.id', $id)
            ->where('t.assigned_to_user_id', Auth::id())
            ->first();

        if (!$thread) {
            abort(403, 'Percakapan ini tidak termasuk thread yang Anda tangani.');
        }

        if ($thread->status === 'resolved') {
            return back()->withErrors([
                'message' => 'Percakapan sudah selesai. Tidak dapat mengirim balasan lagi.',
            ]);
        }

        $nis = trim((string) ($thread->nis ?? ''));

        $siswa = $nis !== '' ? $this->fetchSiswaDetailByNis($nis) : null;

        $target = $siswa
            ? $this->pickPhone($siswa['no_hp_ayah'] ?? null, $siswa['no_hp_ibu'] ?? null)
            : null;

        $sendResult = [
            'success' => false,
            'external_id' => null,
        ];

        if ($target) {
            $sendResult = $this->sendToWhatsappAdmin($target, $data['message']);
        } else {
            Log::warning('[CHAT][ADMIN] WA target kosong saat reply(), fallback simpan via web', [
                'thread_id' => $id,
                'nis' => $nis,
            ]);
        }

        DB::table('chat_messages')->insert([
            'thread_id' => (int) $thread->id,
            'direction' => 'out',
            'channel' => 'web',
            'sender_type' => 'admin',
            'sender_id' => Auth::id(),
            'message_type' => 'teks',
            'message_status' => $target
                ? ($sendResult['success'] ? 'terkirim' : 'gagal')
                : 'terkirim',
            'body' => $data['message'],
            'external_id' => $sendResult['external_id'],
            'delivered_at' => $sendResult['success'] ? now() : null,
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('chat_threads')
            ->where('id', $thread->id)
            ->update([
                'assigned_to_user_id' => Auth::id(),
                'status' => 'open',
                'last_channel' => 'web',
                'last_message_at' => now(),
                'updated_at' => now(),
            ]);

        return redirect()
            ->route('admin.chat.show', $thread->id)
            ->with(
                'success',
                $target
                ? ($sendResult['success']
                    ? 'Balasan terkirim.'
                    : 'Balasan tersimpan, tetapi gagal dikirim ke WhatsApp.')
                : 'Balasan tersimpan (nomor WhatsApp tidak tersedia).'
            );
    }

    /* =========================================================
     |  FORM KIRIM MANUAL
     * =======================================================*/
    public function showManualForm()
    {
        return view('admin.chat.manual');
    }

    /* =========================================================
     |  KIRIM MANUAL KE NOMOR WA
     * =======================================================*/
    public function sendManualMessage(Request $request)
    {
        $data = $request->validate([
            'phone' => ['required', 'string'],
            'message' => ['required', 'string'],
        ], [], [
            'phone' => 'Nomor WhatsApp',
            'message' => 'Pesan',
        ]);

        $phone = $this->normalizeMsisdn($data['phone']);

        if (!$phone) {
            return back()
                ->withErrors(['phone' => 'Nomor WhatsApp tidak valid.'])
                ->withInput();
        }

        $sendResult = $this->sendToWhatsappAdmin($phone, $data['message']);

        if (!$sendResult['success']) {
            return back()
                ->withErrors(['phone' => 'Pesan gagal dikirim ke WhatsApp.'])
                ->withInput();
        }

        return back()->with('success', 'Pesan manual berhasil dikirim.');
    }

    /* =========================================================
     |  HELPER: THREAD LIST
     * =======================================================*/
    private function buildThreadList(int $adminUserId, string $q = ''): Collection
    {
        /*
        |----------------------------------------------------------------------
        | Penting:
        | Jangan filter pencarian langsung di query DB hanya berdasarkan u.name.
        | Nama siswa, nama ayah, nama ibu, rombel, foto, dan nomor HP baru bisa
        | diketahui setelah mengambil detail siswa dari API SIA. Karena itu,
        | thread diambil dulu, diperkaya dari SIA, lalu difilter memakai collection.
        |----------------------------------------------------------------------
        */
        $threads = DB::table('chat_threads as t')
            ->leftJoin('users as u', 'u.id', '=', 't.owner_parent_id')
            ->leftJoin('chat_messages as m', function ($join) {
                $join->on('m.thread_id', '=', 't.id')
                    ->whereRaw('m.id = (SELECT MAX(id) FROM chat_messages WHERE thread_id = t.id)');
            })
            ->selectRaw('
                t.id,
                t.status,
                t.last_channel,
                t.last_message_at,
                t.assigned_to_user_id,
                m.body as last_message_body,
                u.id as user_parent_id,
                u.name as user_parent_name,
                u.sia_user_id as nis,
                (
                    SELECT COUNT(*)
                    FROM chat_messages cmu
                    WHERE cmu.thread_id = t.id
                      AND cmu.sender_type = "parent"
                      AND cmu.read_at IS NULL
                ) as unread_count
            ')
            ->where('t.assigned_to_user_id', $adminUserId)
            ->orderByDesc('t.last_message_at')
            ->orderByDesc('t.id')
            ->get();

        $nisList = $threads
            ->pluck('nis')
            ->filter(fn($nis) => trim((string) $nis) !== '')
            ->unique()
            ->values()
            ->all();

        $siswaMap = [];

        foreach ($nisList as $nis) {
            $detail = $this->fetchSiswaDetailByNis((string) $nis);

            if ($detail) {
                $siswaMap[(string) $nis] = $detail;
            }
        }

        $threads = $threads->map(function ($row) use ($siswaMap) {
            $nisKey = (string) ($row->nis ?? '');
            $s = $nisKey !== '' && isset($siswaMap[$nisKey]) ? $siswaMap[$nisKey] : null;
            $photo = $s ? $this->normalizeSiswaPhotoFields($s) : null;

            $row->siswa_id = $s['id'] ?? null;
            $row->student_name = $s['nama'] ?? null;
            $row->nama_ayah = $s['nama_ayah'] ?? null;
            $row->no_hp_ayah = $s['no_hp_ayah'] ?? null;
            $row->nama_ibu = $s['nama_ibu'] ?? null;
            $row->no_hp_ibu = $s['no_hp_ibu'] ?? null;
            $row->rombel_nama = $s ? $this->extractRombelNama($s) : null;

            $row->foto = $photo['foto'] ?? null;
            $row->foto_url = $photo['foto_url'] ?? null;
            $row->photo_url = $photo['photo_url'] ?? null;
            $row->avatar = $photo['avatar'] ?? null;
            $row->foto_siswa = $photo['foto_siswa'] ?? null;
            $row->foto_src = $photo['foto_src'] ?? null;
            $row->preview_foto = $photo['preview_foto'] ?? null;
            $row->student_photo_url = $photo['foto_src'] ?? null;
            $row->student_photo = $photo['foto_src'] ?? null;

            $parentName = $row->nama_ayah ?: $row->nama_ibu ?: $row->user_parent_name;

            if (!$parentName || trim((string) $parentName) === '') {
                $parentName = $row->user_parent_name ?: 'Orang Tua';
            }

            $row->parent_name = $parentName;

            return $row;
        });

        if (trim($q) !== '') {
            $threads = $threads
                ->filter(fn($row) => $this->threadMatchesKeyword($row, $q))
                ->values();
        }

        return $threads;
    }

    private function buildSingleThreadItem(int $threadId, int $adminUserId): ?object
    {
        $thread = DB::table('chat_threads as t')
            ->leftJoin('users as u', 'u.id', '=', 't.owner_parent_id')
            ->leftJoin('chat_messages as m', function ($join) {
                $join->on('m.thread_id', '=', 't.id')
                    ->whereRaw('m.id = (SELECT MAX(id) FROM chat_messages WHERE thread_id = t.id)');
            })
            ->selectRaw('
                t.id,
                t.status,
                t.last_channel,
                t.last_message_at,
                t.assigned_to_user_id,
                m.body as last_message_body,
                u.id as user_parent_id,
                u.name as user_parent_name,
                u.sia_user_id as nis,
                (
                    SELECT COUNT(*)
                    FROM chat_messages cmu
                    WHERE cmu.thread_id = t.id
                      AND cmu.sender_type = "parent"
                      AND cmu.read_at IS NULL
                ) as unread_count
            ')
            ->where('t.id', $threadId)
            ->where('t.assigned_to_user_id', $adminUserId)
            ->first();

        if (!$thread) {
            return null;
        }

        if (!empty($thread->nis)) {
            $detail = $this->fetchSiswaDetailByNis((string) $thread->nis);

            if ($detail) {
                $photo = $this->normalizeSiswaPhotoFields($detail);

                $thread->siswa_id = $detail['id'] ?? null;
                $thread->student_name = $detail['nama'] ?? null;
                $thread->nama_ayah = $detail['nama_ayah'] ?? null;
                $thread->no_hp_ayah = $detail['no_hp_ayah'] ?? null;
                $thread->nama_ibu = $detail['nama_ibu'] ?? null;
                $thread->no_hp_ibu = $detail['no_hp_ibu'] ?? null;
                $thread->rombel_nama = $this->extractRombelNama($detail);

                $thread->foto = $photo['foto'];
                $thread->foto_url = $photo['foto_url'];
                $thread->photo_url = $photo['photo_url'];
                $thread->avatar = $photo['avatar'];
                $thread->foto_siswa = $photo['foto_siswa'];
                $thread->foto_src = $photo['foto_src'];
                $thread->preview_foto = $photo['preview_foto'];
                $thread->student_photo_url = $photo['foto_src'];
                $thread->student_photo = $photo['foto_src'];

                $parentName = $detail['nama_ayah'] ?? $detail['nama_ibu'] ?? $thread->user_parent_name;
                $thread->parent_name = $parentName ?: ($thread->user_parent_name ?: 'Orang Tua');
            } else {
                $thread->siswa_id = null;
                $thread->student_name = null;
                $thread->nama_ayah = null;
                $thread->no_hp_ayah = null;
                $thread->nama_ibu = null;
                $thread->no_hp_ibu = null;
                $thread->rombel_nama = null;

                $thread->foto = null;
                $thread->foto_url = null;
                $thread->photo_url = null;
                $thread->avatar = null;
                $thread->foto_siswa = null;
                $thread->foto_src = null;
                $thread->preview_foto = null;
                $thread->student_photo_url = null;
                $thread->student_photo = null;

                $thread->parent_name = $thread->user_parent_name ?: 'Orang Tua';
            }
        } else {
            $thread->siswa_id = null;
            $thread->student_name = null;
            $thread->nama_ayah = null;
            $thread->no_hp_ayah = null;
            $thread->nama_ibu = null;
            $thread->no_hp_ibu = null;
            $thread->rombel_nama = null;

            $thread->foto = null;
            $thread->foto_url = null;
            $thread->photo_url = null;
            $thread->avatar = null;
            $thread->foto_siswa = null;
            $thread->foto_src = null;
            $thread->preview_foto = null;
            $thread->student_photo_url = null;
            $thread->student_photo = null;

            $thread->parent_name = $thread->user_parent_name ?: 'Orang Tua';
        }

        return $thread;
    }

    private function threadMatchesKeyword(object $row, string $q): bool
    {
        $keyword = $this->normalizeSearchText($q);

        if ($keyword === '') {
            return true;
        }

        $searchText = $this->normalizeSearchText(implode(' ', array_filter([
            $row->parent_name ?? null,
            $row->user_parent_name ?? null,
            $row->student_name ?? null,
            $row->nis ?? null,
            $row->nama_ayah ?? null,
            $row->nama_ibu ?? null,
            $row->no_hp_ayah ?? null,
            $row->no_hp_ibu ?? null,
            $row->rombel_nama ?? null,
            $row->last_message_body ?? null,
            $row->status ?? null,
            $row->last_channel ?? null,
        ], fn($value) => !is_null($value) && trim((string) $value) !== '')));

        return str_contains($searchText, $keyword);
    }

    private function normalizeSearchText(?string $value): string
    {
        $value = mb_strtolower(trim((string) $value));
        $value = preg_replace('/\s+/', ' ', $value);
        $value = preg_replace('/[^\p{L}\p{N}\s@._+-]/u', ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value);

        return trim((string) $value);
    }

    /* =========================================================
     |  UTIL
     * =======================================================*/
    private function extractRombelNama(array $siswa): ?string
    {
        return $siswa['rombel_nama']
            ?? $siswa['nama_rombel']
            ?? $siswa['rombel']['nama_rombel']
            ?? $siswa['rombel']['nama']
            ?? $siswa['rombel_aktif']['nama_rombel']
            ?? $siswa['rombel_aktif']['nama']
            ?? null;
    }

    private function extractStudentPhoto(array $siswa): ?string
    {
        return $this->resolveSiswaPhotoUrlFromRow($siswa);
    }

    private function normalizeSiswaPhotoFields(array $row): array
    {
        $foto = $this->pickString(
            $row['foto'] ?? null,
            data_get($row, 'siswa.foto')
        );

        $fotoUrl = $this->pickString(
            $row['foto_url'] ?? null,
            data_get($row, 'siswa.foto_url')
        );

        $photoUrl = $this->pickString(
            $row['photo_url'] ?? null,
            data_get($row, 'siswa.photo_url')
        );

        $avatar = $this->pickString(
            $row['avatar'] ?? null,
            data_get($row, 'siswa.avatar')
        );

        $fotoSiswa = $this->pickString(
            $row['foto_siswa'] ?? null,
            data_get($row, 'siswa.foto_siswa')
        );

        $fotoSrc = $this->resolveSiswaPhotoUrlFromRow($row);

        return [
            'foto' => $foto,
            'foto_url' => $fotoUrl,
            'photo_url' => $photoUrl,
            'avatar' => $avatar,
            'foto_siswa' => $fotoSiswa,
            'foto_src' => $fotoSrc,
            'preview_foto' => $fotoSrc,
        ];
    }

    private function resolveSiswaPhotoUrlFromRow(array $row): ?string
    {
        $foto = $this->pickString(
            $row['foto_src'] ?? null,
            $row['student_photo_url'] ?? null,
            $row['student_photo'] ?? null,
            $row['preview_foto'] ?? null,
            $row['foto_url'] ?? null,
            data_get($row, 'siswa.foto_url'),
            $row['photo_url'] ?? null,
            data_get($row, 'siswa.photo_url'),
            $row['avatar'] ?? null,
            data_get($row, 'siswa.avatar'),
            $row['foto'] ?? null,
            data_get($row, 'siswa.foto'),
            $row['foto_siswa'] ?? null,
            data_get($row, 'siswa.foto_siswa'),
            $row['photo'] ?? null,
            data_get($row, 'siswa.photo'),
            $row['gambar'] ?? null,
            data_get($row, 'siswa.gambar'),
            $row['image'] ?? null,
            data_get($row, 'siswa.image')
        );

        if (!$foto) {
            return null;
        }

        $foto = trim((string) $foto);

        if ($foto === '' || $foto === '-') {
            return null;
        }

        if (filter_var($foto, FILTER_VALIDATE_URL)) {
            return $foto;
        }

        $foto = $this->normalizeRelativePhotoPath($foto);

        if ($foto === '') {
            return null;
        }

        $basename = basename($foto);

        $localCandidates = [
            $foto,
            'sia/' . $foto,
            'foto_siswa/' . $basename,
            'sia/foto_siswa/' . $basename,
            'storage/' . $foto,
            'storage/foto_siswa/' . $basename,
            'storage/sia/foto_siswa/' . $basename,
        ];

        foreach (array_unique(array_filter($localCandidates)) as $relativePath) {
            if (is_file(public_path($relativePath))) {
                return asset($relativePath);
            }
        }

        $siaPublicUrl = $this->siaPublicUrl();

        if ($siaPublicUrl === '') {
            return null;
        }

        if (Str::startsWith($foto, 'public/storage/')) {
            $foto = preg_replace('#^public/#', '', $foto);
            return $siaPublicUrl . '/' . $foto;
        }

        if (Str::startsWith($foto, 'storage/')) {
            return $siaPublicUrl . '/' . $foto;
        }

        if (Str::startsWith($foto, 'foto_siswa/')) {
            return $siaPublicUrl . '/storage/' . $foto;
        }

        if (Str::startsWith($foto, ['uploads/', 'siswa/'])) {
            return $siaPublicUrl . '/' . $foto;
        }

        return $siaPublicUrl . '/storage/foto_siswa/' . $basename;
    }

    private function normalizeRelativePhotoPath(string $path): string
    {
        $path = trim($path);
        $path = str_replace('\\', '/', $path);
        $path = preg_replace('#/+#', '/', $path);
        $path = ltrim($path, '/');

        return $path;
    }

    private function siaPublicUrl(): string
    {
        return rtrim((string) (config('services.sia.public_url') ?: config('services.sia.base_url')), '/');
    }

    private function pickPhone(?string $ayah, ?string $ibu): ?string
    {
        $ayah = trim((string) ($ayah ?? ''));
        $ibu = trim((string) ($ibu ?? ''));

        $num = $ayah !== '' ? $ayah : ($ibu !== '' ? $ibu : '');

        if ($num === '') {
            return null;
        }

        return $this->normalizeMsisdn($num) ?: null;
    }

    private function normalizeMsisdn(?string $s): ?string
    {
        $d = preg_replace('/\D+/', '', (string) $s);

        if ($d === '') {
            return null;
        }

        if (str_starts_with($d, '0')) {
            return '62' . substr($d, 1);
        }

        if (str_starts_with($d, '8')) {
            return '62' . $d;
        }

        return $d;
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

    /**
     * KIRIM WA VIA DEVICE 1 = Admin SMADA
     * Nomor device: 6285601820651
     */
    private function sendToWhatsappAdmin(string $phone, string $message): array
    {
        $token = config('services.fonnte.admin_token');
        $deviceNumber = config('services.fonnte.admin_device_number', '6285601820651');

        $payload = [
            'target' => $phone,
            'message' => $message,
            'device' => $deviceNumber,
        ];

        try {
            $resp = Http::withHeaders([
                'Authorization' => $token,
            ])->post('https://api.fonnte.com/send', $payload);
        } catch (\Throwable $e) {
            report($e);

            Log::warning('[FONNTE][ADMIN] request gagal', [
                'device' => $deviceNumber,
                'target' => $phone,
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'external_id' => null,
            ];
        }

        $json = null;

        try {
            $json = $resp->json();
        } catch (\Throwable $e) {
            $json = null;
        }

        if (!$resp->successful()) {
            Log::warning('[FONNTE][ADMIN] send failed', [
                'device' => $deviceNumber,
                'status' => $resp->status(),
                'body' => $resp->body(),
                'target' => $phone,
            ]);

            return [
                'success' => false,
                'external_id' => null,
            ];
        }

        $externalId = null;

        if (is_array($json)) {
            if (isset($json['id'])) {
                if (is_array($json['id'])) {
                    $externalId = isset($json['id'][0]) ? (string) $json['id'][0] : null;
                } elseif (is_scalar($json['id'])) {
                    $externalId = (string) $json['id'];
                }
            } elseif (isset($json['data']['id']) && is_scalar($json['data']['id'])) {
                $externalId = (string) $json['data']['id'];
            }
        }

        Log::info('[FONNTE][ADMIN] send success', [
            'device' => $deviceNumber,
            'target' => $phone,
            'response' => $json,
        ]);

        return [
            'success' => true,
            'external_id' => $externalId,
        ];
    }

    /**
     * Ambil detail siswa lengkap dari SIA berdasarkan NIS
     */
    private function fetchSiswaDetailByNis(string $nis): ?array
    {
        $nis = trim($nis);

        if ($nis === '') {
            return null;
        }

        try {
            $basic = $this->sia->getSiswaByNis($nis);
        } catch (\Throwable $e) {
            report($e);

            Log::warning('[CHAT][ADMIN] exception getSiswaByNis', [
                'nis' => $nis,
                'message' => $e->getMessage(),
            ]);

            return null;
        }

        if (
            !$basic ||
            !(($basic['status'] ?? false) === true || ($basic['success'] ?? false) === true || ($basic['status'] ?? null) === 'success') ||
            empty($basic['data']) ||
            !is_array($basic['data'])
        ) {
            Log::warning('[CHAT][ADMIN] gagal getSiswaByNis', [
                'nis' => $nis,
                'raw' => $basic,
            ]);

            return null;
        }

        $basicData = $basic['data'];
        $id = $basicData['id'] ?? null;

        if (!$id) {
            $photo = $this->normalizeSiswaPhotoFields($basicData);

            return array_merge($basicData, [
                'foto' => $photo['foto'],
                'foto_url' => $photo['foto_url'],
                'photo_url' => $photo['photo_url'],
                'avatar' => $photo['avatar'],
                'foto_siswa' => $photo['foto_siswa'],
                'foto_src' => $photo['foto_src'],
                'preview_foto' => $photo['preview_foto'],
                'student_photo_url' => $photo['foto_src'],
                'student_photo' => $photo['foto_src'],
            ]);
        }

        try {
            $detail = $this->sia->masterSiswaDetail($id);
        } catch (\Throwable $e) {
            report($e);

            Log::warning('[CHAT][ADMIN] exception masterSiswaDetail', [
                'nis' => $nis,
                'id' => $id,
                'message' => $e->getMessage(),
            ]);

            $photo = $this->normalizeSiswaPhotoFields($basicData);

            return array_merge($basicData, [
                'foto' => $photo['foto'],
                'foto_url' => $photo['foto_url'],
                'photo_url' => $photo['photo_url'],
                'avatar' => $photo['avatar'],
                'foto_siswa' => $photo['foto_siswa'],
                'foto_src' => $photo['foto_src'],
                'preview_foto' => $photo['preview_foto'],
                'student_photo_url' => $photo['foto_src'],
                'student_photo' => $photo['foto_src'],
            ]);
        }

        if (
            !$detail ||
            !(($detail['status'] ?? false) === true || ($detail['success'] ?? false) === true || ($detail['status'] ?? null) === 'success') ||
            empty($detail['data']) ||
            !is_array($detail['data'])
        ) {
            Log::warning('[CHAT][ADMIN] gagal masterSiswaDetail', [
                'nis' => $nis,
                'id' => $id,
                'raw' => $detail,
            ]);

            $photo = $this->normalizeSiswaPhotoFields($basicData);

            return array_merge($basicData, [
                'foto' => $photo['foto'],
                'foto_url' => $photo['foto_url'],
                'photo_url' => $photo['photo_url'],
                'avatar' => $photo['avatar'],
                'foto_siswa' => $photo['foto_siswa'],
                'foto_src' => $photo['foto_src'],
                'preview_foto' => $photo['preview_foto'],
                'student_photo_url' => $photo['foto_src'],
                'student_photo' => $photo['foto_src'],
            ]);
        }

        /*
        |----------------------------------------------------------------------
        | Gabungkan basic + detail agar field dari getSiswaByNis tidak hilang.
        | Pada beberapa endpoint, nomor HP atau rombel bisa muncul di salah satu
        | response saja.
        |----------------------------------------------------------------------
        */
        $merged = array_replace_recursive($basicData, $detail['data']);
        $photo = $this->normalizeSiswaPhotoFields($merged);

        return array_merge($merged, [
            'foto' => $photo['foto'],
            'foto_url' => $photo['foto_url'],
            'photo_url' => $photo['photo_url'],
            'avatar' => $photo['avatar'],
            'foto_siswa' => $photo['foto_siswa'],
            'foto_src' => $photo['foto_src'],
            'preview_foto' => $photo['preview_foto'],
            'student_photo_url' => $photo['foto_src'],
            'student_photo' => $photo['foto_src'],
        ]);
    }

    public function fetchNewMessages(Request $request, int $id)
    {
        $thread = DB::table('chat_threads')
            ->where('id', $id)
            ->where('assigned_to_user_id', Auth::id())
            ->first();

        if (!$thread) {
            return response()->json([
                'success' => false,
                'message' => 'Thread tidak ditemukan atau bukan milik admin ini.',
            ], 403);
        }

        $afterId = (int) $request->query('after_id', 0);

        $messages = DB::table('chat_messages')
            ->where('thread_id', $id)
            ->where('id', '>', $afterId)
            ->orderBy('id')
            ->get();

        if ($messages->isNotEmpty()) {
            DB::table('chat_messages')
                ->where('thread_id', $id)
                ->where('sender_type', 'parent')
                ->whereNull('read_at')
                ->whereIn('id', $messages->pluck('id')->all())
                ->update([
                    'read_at' => now(),
                    'updated_at' => now(),
                ]);
        }

        $formatted = $messages->map(function ($m) {
            $isAdmin = $m->sender_type === 'admin';
            $isGuru = in_array($m->sender_type, ['guru', 'walkel'], true);
            $isParent = $m->sender_type === 'parent';

            $who = $isAdmin
                ? 'Admin'
                : ($isGuru ? 'Wali Kelas' : ($isParent ? 'Ortu' : ucfirst($m->sender_type ?? '')));

            $time = \Carbon\Carbon::parse($m->created_at)
                ->timezone('Asia/Jakarta')
                ->locale('id')
                ->translatedFormat('d M Y, H:i');

            return [
                'id' => (int) $m->id,
                'sender_type' => $m->sender_type,
                'who' => $who,
                'channel' => $m->channel,
                'body' => $m->body,
                'time' => $time . ' WIB',
                'message_status' => $m->message_status,
                'is_outgoing' => $isAdmin || $isGuru,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'messages' => $formatted,
            'last_id' => $formatted->isNotEmpty()
                ? $formatted->last()['id']
                : $afterId,
        ]);
    }
}