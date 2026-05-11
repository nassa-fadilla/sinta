<?php

namespace App\Http\Controllers\Ortu;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Models\User;
use App\Services\SiaClient;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    /**
     * Dashboard Orang Tua
     */
    public function dashboard(SiaClient $sia)
    {
        $user = auth()->user();

        $siswaApi = $this->resolveSiswaApi($sia, $user?->sia_user_id);
        $siswa = $siswaApi ? (object) $siswaApi : null;

        $lastThread = ChatThread::where('owner_parent_id', $user->id)
            ->latest('last_message_at')
            ->first();

        return view('ortu.dashboard', compact('user', 'siswa', 'lastThread'));
    }

    /**
     * Daftar seluruh thread milik ortu
     */
    public function index(Request $request, SiaClient $sia)
    {
        $userId = auth()->id();
        $q = trim((string) $request->get('q', ''));

        $owner = auth()->user();
        $siswaApi = $this->resolveSiswaApi($sia, $owner?->sia_user_id);
        $siswa = $siswaApi ? (object) $siswaApi : null;

        $threads = $this->buildThreadList($userId, $q, $siswaApi, $sia);

        return view('ortu.chat.index', [
            'threads' => $threads,
            'activeThread' => null,
            'messages' => collect(),
            'q' => $q,
            'siswa' => $siswa,
            'sidebarSiswa' => $this->buildSidebarSiswa($siswaApi, $owner?->sia_user_id),
        ]);
    }

    /**
     * Form membuat chat baru
     * Hanya 2 tujuan: Admin SINTA dan Wali Kelas siswa.
     */
    public function create(SiaClient $sia)
    {
        $user = auth()->user();
        $nis = trim((string) ($user->sia_user_id ?? ''));

        $siswaApi = $this->resolveSiswaApi($sia, $nis);
        $siswa = $siswaApi ? (object) $siswaApi : null;

        $rombelDetail = $this->resolveRombelDetailFromSiswa($sia, $siswaApi);
        $waliKelasApi = $this->resolveWaliKelasApi($sia, $rombelDetail);

        $receivers = collect();

        /*
        |--------------------------------------------------------------------------
        | 1. Admin SINTA
        |--------------------------------------------------------------------------
        */
        $adminUser = User::where('role', 'admin')
            ->orderBy('id')
            ->first();

        if ($adminUser) {
            $adminUser->chat_role_label = 'Admin SINTA';
            $adminUser->chat_role_detail = 'Admin SINTA';
            $adminUser->contact_phone = '085601820651';
            $adminUser->device_label = 'Device 1 - Admin SMADA';
            $adminUser->display_name = $adminUser->name;
            $receivers->push($adminUser);
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Wali Kelas siswa dari API SIA
        |--------------------------------------------------------------------------
        */
        $waliNama = data_get($waliKelasApi, 'nama')
            ?? data_get($rombelDetail, 'wali_kelas.nama');

        $waliNip = data_get($waliKelasApi, 'nip')
            ?? data_get($rombelDetail, 'wali_kelas.nip');

        $waliNuptk = data_get($waliKelasApi, 'nuptk')
            ?? data_get($rombelDetail, 'wali_kelas.nuptk');

        $waliPhone = data_get($waliKelasApi, 'no_hp')
            ?? data_get($waliKelasApi, 'phone')
            ?? data_get($rombelDetail, 'wali_kelas.no_hp')
            ?? data_get($rombelDetail, 'wali_kelas.phone');

        $waliUser = null;

        if ($waliNuptk || $waliNip) {
            $waliUser = User::query()
                ->whereIn('role', ['walkel', 'guru'])
                ->where(function ($q) use ($waliNuptk, $waliNip) {
                    if ($waliNuptk) {
                        $q->orWhere('sia_user_id', (string) $waliNuptk);
                    }

                    if ($waliNip) {
                        $q->orWhere('sia_user_id', (string) $waliNip);
                    }
                })
                ->orderByRaw("
                    CASE
                        WHEN role = 'walkel' THEN 1
                        WHEN role = 'guru' THEN 2
                        ELSE 9
                    END
                ")
                ->orderBy('id')
                ->first();
        }

        if ($waliUser) {
            $waliRombelLabel = $this->resolveWalkelRombelLabel($sia, $waliUser, $waliKelasApi, $rombelDetail);

            $waliUser->chat_role_label = 'Wali Kelas';
            $waliUser->chat_role_detail = $waliRombelLabel
                ? 'Wali Kelas • ' . $waliRombelLabel
                : 'Wali Kelas';
            $waliUser->contact_phone = $waliPhone ?: '-';
            $waliUser->device_label = 'Device 2 - Wali Kelas';
            $waliUser->display_name = $waliNama ?: $waliUser->name;
            $waliUser->rombel_label = $waliRombelLabel;
            $receivers->push($waliUser);
        }

        return view('ortu.chat.create', [
            'receivers' => $receivers,
            'siswa' => $siswa,
            'rombelAktif' => is_array(data_get($siswaApi, 'rombel_aktif'))
                ? (object) data_get($siswaApi, 'rombel_aktif')
                : null,
            'sidebarSiswa' => $this->buildSidebarSiswa($siswaApi, $nis),
        ]);
    }

    /**
     * Simpan thread baru & kirim pesan awal
     */
    public function store(Request $request)
    {
        $request->validate([
            'assigned_to_user_id' => 'nullable|exists:users,id',
            'receiver_id' => 'nullable|exists:users,id',
            'message' => 'required|string|max:4000',
        ]);

        $userId = auth()->id();
        $assignedUserId = $request->input('assigned_to_user_id') ?: $request->input('receiver_id');

        if (!$assignedUserId) {
            return back()
                ->withErrors(['assigned_to_user_id' => 'Tujuan percakapan harus dipilih.'])
                ->withInput();
        }

        $thread = ChatThread::where('owner_parent_id', $userId)
            ->where('assigned_to_user_id', $assignedUserId)
            ->whereIn('status', ['open', 'pending'])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->first();

        if (!$thread) {
            $thread = ChatThread::create([
                'owner_parent_id' => $userId,
                'assigned_to_user_id' => $assignedUserId,
                'status' => 'open',
                'last_channel' => 'web',
                'last_message_at' => now(),
            ]);
        } else {
            $thread->update([
                'status' => 'open',
                'last_channel' => 'web',
                'last_message_at' => now(),
            ]);
        }

        ChatMessage::create([
            'thread_id' => $thread->id,
            'direction' => 'out',
            'channel' => 'web',
            'sender_type' => 'parent',
            'sender_id' => $userId,
            'message_type' => 'teks',
            'message_status' => 'terkirim',
            'body' => $request->input('message'),
            'read_at' => null,
        ]);

        $thread->update([
            'last_message_at' => now(),
            'last_channel' => 'web',
        ]);

        return redirect()
            ->route('ortu.chat.show', $thread)
            ->with('ok', 'Chat baru berhasil dibuat.');
    }

    /**
     * Mulai percakapan baru
     */
    public function start(Request $request)
    {
        $userId = auth()->id();

        $thread = ChatThread::where('owner_parent_id', $userId)
            ->whereIn('status', ['open', 'pending'])
            ->latest('updated_at')
            ->first();

        if (!$thread) {
            $thread = ChatThread::create([
                'owner_parent_id' => $userId,
                'assigned_to_user_id' => null,
                'status' => 'open',
                'last_channel' => 'web',
                'last_message_at' => Carbon::now(),
            ]);
        }

        return redirect()->route('ortu.chat.show', $thread);
    }

    /**
     * Detail percakapan
     */
    public function show(Request $request, ChatThread $thread, SiaClient $sia)
    {
        $this->authorizeThread($thread);

        $q = trim((string) $request->get('q', ''));

        ChatMessage::where('thread_id', $thread->id)
            ->whereIn('sender_type', ['admin', 'guru', 'walkel'])
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
                'updated_at' => now(),
            ]);

        $owner = auth()->user();
        $siswaApi = $this->resolveSiswaApi($sia, $owner?->sia_user_id);
        $siswa = $siswaApi ? (object) $siswaApi : null;

        $threads = $this->buildThreadList(auth()->id(), $q, $siswaApi, $sia);

        $thread->load([
            'assignee',
            'owner',
            'messages' => fn($q) => $q->orderBy('id', 'asc'),
            'messages.sender',
        ]);

        if ($thread->assignee) {
            $thread->assignee_role_detail = $this->resolveAssigneeRoleDetail($sia, $thread->assignee);
        }

        if ($siswaApi) {
            $photo = $this->normalizeSiswaPhotoFields($siswaApi);

            $thread->siswa_nama = data_get($siswaApi, 'nama');
            $thread->siswa_nis = data_get($siswaApi, 'nis', $owner?->sia_user_id);
            $thread->siswa_nisn = data_get($siswaApi, 'nisn');
            $thread->siswa_kelas = data_get($siswaApi, 'rombel.nama_rombel')
                ?? data_get($siswaApi, 'rombel_aktif.nama_rombel')
                ?? data_get($siswaApi, 'rombel_nama')
                ?? null;

            $thread->foto = $photo['foto'];
            $thread->foto_url = $photo['foto_url'];
            $thread->photo_url = $photo['photo_url'];
            $thread->avatar = $photo['avatar'];
            $thread->foto_siswa = $photo['foto_siswa'];
            $thread->foto_src = $photo['foto_src'];
            $thread->preview_foto = $photo['preview_foto'];
            $thread->student_photo_url = $photo['foto_src'];
            $thread->student_photo = $photo['foto_src'];
        }

        return view('ortu.chat.index', [
            'threads' => $threads,
            'activeThread' => $thread,
            'messages' => $thread->messages,
            'q' => $q,
            'siswa' => $siswa,
            'sidebarSiswa' => $this->buildSidebarSiswa($siswaApi, $owner?->sia_user_id),
        ]);
    }

    /**
     * Kirim pesan lanjutan
     */
    public function send(Request $request, ChatThread $thread)
    {
        $this->authorizeThread($thread);

        if ($thread->status === 'resolved') {
            return back()->withErrors([
                'message' => 'Percakapan sudah selesai. Silakan buat percakapan baru jika ingin mengirim pesan lagi.'
            ]);
        }

        $data = $request->validate([
            'message' => 'nullable|string|max:4000',
            'body' => 'nullable|string|max:4000',
        ]);

        $text = $data['message'] ?? $data['body'] ?? '';

        if (trim($text) === '') {
            return back()->withErrors(['message' => 'Pesan tidak boleh kosong.']);
        }

        DB::transaction(function () use ($thread, $text) {
            ChatMessage::create([
                'thread_id' => $thread->id,
                'direction' => 'out',
                'channel' => 'web',
                'sender_type' => 'parent',
                'sender_id' => auth()->id(),
                'message_type' => 'teks',
                'message_status' => 'terkirim',
                'body' => $text,
                'read_at' => null,
            ]);

            $thread->update([
                'last_message_at' => now(),
                'last_channel' => 'web',
                'status' => $thread->status === 'pending' ? 'open' : $thread->status,
            ]);
        });

        return back()->with('ok', 'Pesan terkirim.');
    }

    /**
     * Fetch pesan baru tanpa refresh
     */
    public function fetchNewMessages(Request $request, ChatThread $thread)
    {
        $this->authorizeThread($thread);

        $afterId = (int) $request->get('after_id', 0);

        $messages = ChatMessage::with('sender')
            ->where('thread_id', $thread->id)
            ->when($afterId > 0, fn($q) => $q->where('id', '>', $afterId))
            ->orderBy('id', 'asc')
            ->get();

        if ($messages->isNotEmpty()) {
            ChatMessage::where('thread_id', $thread->id)
                ->whereIn('sender_type', ['admin', 'guru', 'walkel'])
                ->whereNull('read_at')
                ->whereIn('id', $messages->pluck('id')->all())
                ->update([
                    'read_at' => now(),
                    'updated_at' => now(),
                ]);
        }

        $formatted = $messages->map(function ($m) {
            $isParent = $m->sender_type === 'parent';

            return [
                'id' => $m->id,
                'body' => $m->body,
                'channel' => $m->channel,
                'sender_type' => $m->sender_type,
                'message_status' => $m->message_status,
                'is_outgoing' => $isParent,
                'who' => $isParent
                    ? 'Anda'
                    : ($m->sender?->name ?? ucfirst($m->sender_type ?? 'Petugas')),
                'time' => Carbon::parse($m->created_at)
                    ->timezone('Asia/Jakarta')
                    ->locale('id')
                    ->translatedFormat('d M Y, H:i') . ' WIB',
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

    private function buildThreadList(int $userId, string $q = '', ?array $siswaApi = null, ?SiaClient $sia = null): Collection
    {
        $photo = $siswaApi ? $this->normalizeSiswaPhotoFields($siswaApi) : null;

        $threads = ChatThread::with(['assignee', 'owner'])
            ->where('owner_parent_id', $userId)
            ->addSelect([
                'last_message_body' => ChatMessage::select('body')
                    ->whereColumn('chat_messages.thread_id', 'chat_threads.id')
                    ->latest('id')
                    ->limit(1),
                'unread_count' => ChatMessage::selectRaw('COUNT(*)')
                    ->whereColumn('chat_messages.thread_id', 'chat_threads.id')
                    ->whereNull('read_at')
                    ->whereIn('sender_type', ['admin', 'guru', 'walkel']),
            ])
            ->orderByDesc('last_message_at')
            ->get()
            ->map(function ($t) use ($sia, $siswaApi, $photo) {
                $t->assignee_role_detail = $sia
                    ? $this->resolveAssigneeRoleDetail($sia, $t->assignee)
                    : $this->resolveLocalRoleLabel($t->assignee?->role);

                if ($siswaApi && $photo) {
                    $t->siswa_nama = data_get($siswaApi, 'nama');
                    $t->siswa_nis = data_get($siswaApi, 'nis');
                    $t->siswa_nisn = data_get($siswaApi, 'nisn');
                    $t->siswa_kelas = data_get($siswaApi, 'rombel.nama_rombel')
                        ?? data_get($siswaApi, 'rombel_aktif.nama_rombel')
                        ?? data_get($siswaApi, 'rombel_nama')
                        ?? null;

                    $t->foto = $photo['foto'];
                    $t->foto_url = $photo['foto_url'];
                    $t->photo_url = $photo['photo_url'];
                    $t->avatar = $photo['avatar'];
                    $t->foto_siswa = $photo['foto_siswa'];
                    $t->foto_src = $photo['foto_src'];
                    $t->preview_foto = $photo['preview_foto'];
                    $t->student_photo_url = $photo['foto_src'];
                    $t->student_photo = $photo['foto_src'];
                }

                return $t;
            });

        if ($q !== '') {
            $needle = strtolower($q);

            $threads = $threads->filter(function ($t) use ($needle, $siswaApi) {
                $assigneeName = strtolower((string) ($t->assignee?->name ?? ''));
                $assigneeRole = strtolower((string) ($t->assignee?->role ?? ''));
                $assigneeRoleDetail = strtolower((string) ($t->assignee_role_detail ?? ''));

                $matchAssignee =
                    str_contains($assigneeName, $needle) ||
                    str_contains($assigneeRole, $needle) ||
                    str_contains($assigneeRoleDetail, $needle);

                $matchSiswa = false;

                if ($siswaApi) {
                    $matchSiswa =
                        str_contains(strtolower((string) data_get($siswaApi, 'nama', '')), $needle) ||
                        str_contains(strtolower((string) data_get($siswaApi, 'nis', '')), $needle) ||
                        str_contains(strtolower((string) data_get($siswaApi, 'nisn', '')), $needle);
                }

                return $matchAssignee || $matchSiswa;
            })->values();
        }

        return $threads;
    }

    private function authorizeThread(ChatThread $thread): void
    {
        abort_unless($thread->owner_parent_id === auth()->id(), 403);
    }

    private function resolveSiswaApi(SiaClient $sia, ?string $nis): ?array
    {
        $nis = trim((string) $nis);

        if ($nis === '') {
            return null;
        }

        try {
            $resp = $sia->getSiswaByNis($nis);

            if (
                is_array($resp) &&
                (($resp['success'] ?? false) === true || ($resp['status'] ?? false) === true) &&
                !empty($resp['data']) &&
                is_array($resp['data'])
            ) {
                $basicData = $resp['data'];

                $detailData = [];

                if (!empty($basicData['id'])) {
                    try {
                        $detailResp = $sia->masterSiswaDetail($basicData['id']);

                        if (
                            is_array($detailResp) &&
                            (($detailResp['success'] ?? false) === true || ($detailResp['status'] ?? false) === true) &&
                            !empty($detailResp['data']) &&
                            is_array($detailResp['data'])
                        ) {
                            $detailData = $detailResp['data'];
                        }
                    } catch (\Throwable $e) {
                        report($e);
                    }
                }

                $merged = array_replace_recursive($basicData, $detailData);
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
        } catch (\Throwable $e) {
            report($e);
        }

        return null;
    }

    private function resolveRombelDetailFromSiswa(SiaClient $sia, ?array $siswaApi): ?array
    {
        if (!$siswaApi) {
            return null;
        }

        $rombelId = data_get($siswaApi, 'rombel.id')
            ?? data_get($siswaApi, 'rombel.rombel_id')
            ?? data_get($siswaApi, 'rombel_id')
            ?? data_get($siswaApi, 'rombel_aktif.id');

        if (!$rombelId) {
            return null;
        }

        try {
            $resp = $sia->masterRombelDetail($rombelId);

            if (
                is_array($resp) &&
                (($resp['success'] ?? false) === true || ($resp['status'] ?? false) === true) &&
                !empty($resp['data']) &&
                is_array($resp['data'])
            ) {
                return $resp['data'];
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return null;
    }

    private function resolveWaliKelasApi(SiaClient $sia, ?array $rombelDetail): ?array
    {
        if (!$rombelDetail) {
            return null;
        }

        $nuptk = data_get($rombelDetail, 'wali_kelas.nuptk');
        $nip = data_get($rombelDetail, 'wali_kelas.nip');
        $guruId = data_get($rombelDetail, 'wali_kelas.id') ?? data_get($rombelDetail, 'guru_id');

        $keys = array_filter([
            $nuptk,
            $nip,
            $guruId,
        ], fn($v) => !is_null($v) && $v !== '');

        foreach ($keys as $key) {
            try {
                $resp = $sia->getGuruByKey($key);

                if (
                    is_array($resp) &&
                    (($resp['success'] ?? false) === true || ($resp['status'] ?? false) === true) &&
                    !empty($resp['data']) &&
                    is_array($resp['data'])
                ) {
                    return $resp['data'];
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return null;
    }

    private function buildSidebarSiswa(?array $siswaApi, ?string $nis): ?array
    {
        if (!$siswaApi && !$nis) {
            return null;
        }

        $photo = $siswaApi ? $this->normalizeSiswaPhotoFields($siswaApi) : [
            'foto' => null,
            'foto_url' => null,
            'photo_url' => null,
            'avatar' => null,
            'foto_siswa' => null,
            'foto_src' => null,
            'preview_foto' => null,
        ];

        return [
            'nama' => data_get($siswaApi, 'nama', 'Siswa'),
            'nis' => data_get($siswaApi, 'nis', $nis ?: '-'),
            'nisn' => data_get($siswaApi, 'nisn', '-'),
            'kelas' => data_get($siswaApi, 'rombel.nama_rombel')
                ?? data_get($siswaApi, 'rombel_aktif.nama_rombel')
                ?? data_get($siswaApi, 'rombel_nama')
                ?? '-',

            'foto' => $photo['foto'],
            'foto_url' => $photo['foto_url'],
            'photo_url' => $photo['photo_url'],
            'avatar' => $photo['avatar'],
            'foto_siswa' => $photo['foto_siswa'],
            'foto_src' => $photo['foto_src'],
            'preview_foto' => $photo['preview_foto'],
            'student_photo_url' => $photo['foto_src'],
            'student_photo' => $photo['foto_src'],
        ];
    }

    private function resolveAssigneeRoleDetail(SiaClient $sia, $assignee): string
    {
        $role = strtolower((string) ($assignee->role ?? ''));

        if ($role === 'admin') {
            return 'Admin';
        }

        if (in_array($role, ['walkel', 'guru'], true)) {
            $rombelLabel = $this->resolveWalkelRombelLabel($sia, $assignee);

            if ($role === 'walkel') {
                return $rombelLabel ? 'Wali Kelas • ' . $rombelLabel : 'Wali Kelas';
            }

            return $rombelLabel ? 'Guru • ' . $rombelLabel : 'Guru';
        }

        return 'Pihak Sekolah';
    }

    private function resolveLocalRoleLabel(?string $role): string
    {
        return match (strtolower((string) $role)) {
            'admin' => 'Admin',
            'walkel' => 'Wali Kelas',
            'guru' => 'Guru',
            default => 'Pihak Sekolah',
        };
    }

    private function resolveWalkelRombelLabel(
        SiaClient $sia,
        $user,
        ?array $guruApi = null,
        ?array $rombelDetail = null
    ): ?string {
        if ($rombelDetail) {
            $namaRombel = data_get($rombelDetail, 'nama_rombel');
            $tingkat = data_get($rombelDetail, 'tingkat');

            if ($namaRombel) {
                return $this->formatRombelLabel($tingkat, $namaRombel);
            }
        }

        $guruData = $guruApi;

        if (!$guruData) {
            $identifier = trim((string) ($user->sia_user_id ?? ''));

            if ($identifier === '') {
                return null;
            }

            try {
                $resp = $sia->getGuruByKey($identifier);

                if (
                    is_array($resp) &&
                    (($resp['success'] ?? false) === true || ($resp['status'] ?? false) === true) &&
                    !empty($resp['data']) &&
                    is_array($resp['data'])
                ) {
                    $guruData = $resp['data'];
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        if (!$guruData) {
            return null;
        }

        $guruId = data_get($guruData, 'id');

        if (!$guruId) {
            return null;
        }

        try {
            $rombelResp = $sia->masterRombel(null, [
                'guru_id' => $guruId,
                'aktif' => 1,
            ]);

            $rombelList = collect($rombelResp['data'] ?? [])
                ->filter(function ($rombel) use ($guruId) {
                    $waliId = data_get($rombel, 'wali_kelas.id')
                        ?? data_get($rombel, 'wali_kelas_id');

                    return (string) $waliId === (string) $guruId;
                })
                ->map(function ($rombel) {
                    return $this->formatRombelLabel(
                        data_get($rombel, 'tingkat'),
                        data_get($rombel, 'nama_rombel')
                    );
                })
                ->filter()
                ->unique()
                ->values()
                ->all();

            if (!empty($rombelList)) {
                return implode(', ', $rombelList);
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return null;
    }

    private function formatRombelLabel(?string $tingkat, ?string $namaRombel): ?string
    {
        $tingkat = trim((string) $tingkat);
        $namaRombel = trim((string) $namaRombel);

        if ($namaRombel === '') {
            return null;
        }

        if ($tingkat === '') {
            return $namaRombel;
        }

        $upperTingkat = strtoupper($tingkat);
        $upperNama = strtoupper($namaRombel);

        if (str_starts_with($upperNama, $upperTingkat)) {
            return $namaRombel;
        }

        return $tingkat . $namaRombel;
    }

    /*
    |--------------------------------------------------------------------------
    | FOTO SISWA
    |--------------------------------------------------------------------------
    */

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