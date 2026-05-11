<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Services\SiaClient;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    protected SiaClient $sia;

    public function __construct(SiaClient $sia)
    {
        $this->sia = $sia;
    }

    /* =========================================================
     |  LIST THREAD (Riwayat Chat Wali Kelas)
     * =======================================================*/
    public function index(Request $request)
    {
        [$user, $role, $guru] = $this->getCurrentGuruOrAbort();

        $q = trim((string) $request->query('q', ''));

        $allowedParents = $this->resolveAllowedParentUsers($guru, $role);
        $threads = $this->buildThreadList($allowedParents, $user->id, $q);

        return view('guru.chat.index', [
            'user' => $user,
            'guruSia' => $guru,
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
        [$user, $role, $guru] = $this->getCurrentGuruOrAbort();

        $parents = $this->resolveAllowedParentUsers($guru, $role)
            ->sortBy('siswa_nama')
            ->values();

        Log::info('[CHAT][WALKEL] parents di create()', [
            'guru_user_id' => $user->id,
            'guru_id_sia' => $guru->id ?? null,
            'role' => $role,
            'count' => $parents->count(),
        ]);

        return view('guru.chat.create', [
            'user' => $user,
            'guruSia' => $guru,
            'parents' => $parents,
        ]);
    }

    /* =========================================================
     |  PROSES KIRIM BARU (Wali Kelas -> Ortu)
     * =======================================================*/
    public function store(Request $request)
    {
        [$user, $role, $guru] = $this->getCurrentGuruOrAbort();

        $data = $request->validate([
            'owner_parent_id' => ['required', 'integer'],
            'message' => ['required', 'string'],
        ], [], [
            'owner_parent_id' => 'Orang Tua / Siswa',
            'message' => 'Pesan',
        ]);

        $allowedParents = $this->resolveAllowedParentUsers($guru, $role);
        $parent = $allowedParents->firstWhere('user_ortu_id', (int) $data['owner_parent_id']);

        if (!$parent) {
            return back()
                ->withErrors(['owner_parent_id' => 'Orang tua tersebut tidak termasuk siswa binaan Anda.'])
                ->withInput();
        }

        $thread = DB::table('chat_threads')
            ->where('owner_parent_id', $parent->user_ortu_id)
            ->where('assigned_to_user_id', $user->id)
            ->whereIn('status', ['open', 'pending'])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->first();

        if (!$thread) {
            $threadId = DB::table('chat_threads')->insertGetId([
                'owner_parent_id' => $parent->user_ortu_id,
                'assigned_to_user_id' => $user->id,
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
                    'assigned_to_user_id' => $user->id,
                    'status' => 'open',
                    'last_channel' => 'web',
                    'last_message_at' => now(),
                    'updated_at' => now(),
                ]);
        }

        $sendResult = [
            'success' => false,
            'external_id' => null,
        ];

        if ($parent->wa_target) {
            $sendResult = $this->sendToWhatsappGuru($parent->wa_target, $data['message']);
        } else {
            Log::warning('[CHAT][WALKEL] WA target kosong di store()', [
                'guru_user_id' => $user->id,
                'parent_user_id' => $parent->user_ortu_id,
                'nis' => $parent->nis,
            ]);
        }

        DB::table('chat_messages')->insert([
            'thread_id' => $threadId,
            'direction' => 'out',
            'channel' => 'web',
            'sender_type' => 'walkel',
            'sender_id' => $user->id,
            'message_type' => 'teks',
            'message_status' => $parent->wa_target
                ? ($sendResult['success'] ? 'terkirim' : 'gagal')
                : 'terkirim',
            'body' => $data['message'],
            'external_id' => $sendResult['external_id'],
            'delivered_at' => $sendResult['success'] ? now() : null,

            /*
            |--------------------------------------------------------------------------
            | Penting:
            | read_at dibuat NULL agar pesan wali kelas dihitung sebagai pesan baru
            | di sisi orang tua sampai orang tua membuka room chat tersebut.
            |--------------------------------------------------------------------------
            */
            'read_at' => null,

            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('guru.chat.show', $threadId)
            ->with(
                'success',
                $parent->wa_target
                ? ($sendResult['success']
                    ? 'Pesan terkirim & tersimpan.'
                    : 'Pesan tersimpan, tetapi gagal dikirim ke WhatsApp.')
                : 'Pesan tersimpan (nomor WhatsApp tidak tersedia).'
            );
    }

    /* =========================================================
     |  DETAIL THREAD
     * =======================================================*/
    public function show(Request $request, int $id)
    {
        [$user, $role, $guru] = $this->getCurrentGuruOrAbort();

        $q = trim((string) $request->query('q', ''));

        $activeThread = DB::table('chat_threads as t')
            ->join('users as u', 'u.id', '=', 't.owner_parent_id')
            ->select(
                't.*',
                'u.name as parent_name',
                'u.sia_user_id as nis'
            )
            ->where('t.id', $id)
            ->where('t.assigned_to_user_id', $user->id)
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

        $allowedParents = $this->resolveAllowedParentUsers($guru, $role);
        $threads = $this->buildThreadList($allowedParents, $user->id, $q);

        if (!$threads->contains(fn($t) => (int) $t->id === (int) $activeThread->id)) {
            $threadTambahan = $this->bangunThreadSidebarDariThreadAktif($activeThread);

            if ($threadTambahan) {
                $threads = collect([$threadTambahan])
                    ->concat($threads)
                    ->unique('id')
                    ->values();
            }
        }

        $detail = $activeThread->nis
            ? $this->fetchSiswaDetailByNis(trim((string) $activeThread->nis))
            : null;

        $wa_target = null;

        if ($detail) {
            $photo = $this->normalizeSiswaPhotoFields($detail);

            $activeThread->student_name = $detail['nama'] ?? null;
            $activeThread->nama_ayah = $detail['nama_ayah'] ?? null;
            $activeThread->nama_ibu = $detail['nama_ibu'] ?? null;
            $activeThread->rombel_nama = data_get($detail, 'rombel_aktif.nama_rombel')
                ?? data_get($detail, 'rombel.nama_rombel')
                ?? data_get($detail, 'rombel.nama')
                ?? null;

            $activeThread->foto = $photo['foto'];
            $activeThread->foto_url = $photo['foto_url'];
            $activeThread->photo_url = $photo['photo_url'];
            $activeThread->avatar = $photo['avatar'];
            $activeThread->foto_siswa = $photo['foto_siswa'];
            $activeThread->foto_src = $photo['foto_src'];
            $activeThread->preview_foto = $photo['preview_foto'];
            $activeThread->student_photo_url = $photo['foto_src'];

            $parentName = $detail['nama_ayah'] ?? $detail['nama_ibu'] ?? $activeThread->parent_name;
            $activeThread->parent_name = $parentName ?: $activeThread->parent_name;

            $wa_target = $this->pickPhone(
                $detail['no_hp_ayah'] ?? null,
                $detail['no_hp_ibu'] ?? null
            );
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
        }

        $messages = DB::table('chat_messages')
            ->where('thread_id', $id)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        return view('guru.chat.index', [
            'user' => $user,
            'guruSia' => $guru,
            'threads' => $threads,
            'activeThread' => $activeThread,
            'messages' => $messages,
            'wa_target' => $wa_target,
            'q' => $q,
        ]);
    }

    /* =========================================================
     |  AUTO FETCH PESAN BARU
     * =======================================================*/
    public function fetchNewMessages(Request $request, int $thread)
    {
        [$user, $role, $guru] = $this->getCurrentGuruOrAbort();

        $activeThread = DB::table('chat_threads as t')
            ->join('users as u', 'u.id', '=', 't.owner_parent_id')
            ->select(
                't.*',
                'u.name as parent_name',
                'u.sia_user_id as nis'
            )
            ->where('t.id', $thread)
            ->where('t.assigned_to_user_id', $user->id)
            ->first();

        if (!$activeThread) {
            return response()->json([
                'success' => false,
                'message' => 'Thread tidak ditemukan atau tidak dapat diakses.',
            ], 403);
        }

        $lastId = (int) $request->query('last_id', 0);

        $newMessages = DB::table('chat_messages')
            ->where('thread_id', $thread)
            ->where('id', '>', $lastId)
            ->orderBy('id', 'asc')
            ->get();

        if ($newMessages->isEmpty()) {
            return response()->json([
                'success' => true,
                'messages' => [],
                'last_id' => $lastId,
            ]);
        }

        DB::table('chat_messages')
            ->where('thread_id', $thread)
            ->whereIn('id', $newMessages->pluck('id')->all())
            ->where('sender_type', 'parent')
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
                'updated_at' => now(),
            ]);

        $messages = $newMessages->map(function ($m) {
            return $this->formatMessageForRealtime($m);
        })->values();

        return response()->json([
            'success' => true,
            'messages' => $messages,
            'last_id' => $messages->isNotEmpty()
                ? $messages->last()['id']
                : $lastId,
        ]);
    }

    /* =========================================================
     |  BALAS PESAN (Wali Kelas -> Ortu)
     * =======================================================*/
    public function send(Request $request, int $id)
    {
        [$user, $role, $guru] = $this->getCurrentGuruOrAbort();

        $data = $request->validate([
            'message' => ['required', 'string'],
        ], [], [
            'message' => 'Pesan',
        ]);

        $thread = DB::table('chat_threads as t')
            ->join('users as u', 'u.id', '=', 't.owner_parent_id')
            ->select('t.*', 'u.sia_user_id as nis')
            ->where('t.id', $id)
            ->where('t.assigned_to_user_id', $user->id)
            ->first();

        if (!$thread) {
            abort(403, 'Percakapan ini tidak termasuk thread yang Anda tangani.');
        }

        if ($thread->status === 'resolved') {
            return back()->withErrors([
                'message' => 'Percakapan sudah selesai. Tidak dapat mengirim balasan lagi.',
            ]);
        }

        $detail = $thread->nis
            ? $this->fetchSiswaDetailByNis(trim((string) $thread->nis))
            : null;

        $target = $detail
            ? $this->pickPhone($detail['no_hp_ayah'] ?? null, $detail['no_hp_ibu'] ?? null)
            : null;

        $sendResult = [
            'success' => false,
            'external_id' => null,
        ];

        if ($target) {
            $sendResult = $this->sendToWhatsappGuru($target, $data['message']);
        } else {
            Log::warning('[CHAT][WALKEL] WA target kosong saat send()', [
                'thread_id' => $id,
                'nis' => $thread->nis,
            ]);
        }

        DB::table('chat_messages')->insert([
            'thread_id' => (int) $thread->id,
            'direction' => 'out',
            'channel' => 'web',
            'sender_type' => 'walkel',
            'sender_id' => $user->id,
            'message_type' => 'teks',
            'message_status' => $target
                ? ($sendResult['success'] ? 'terkirim' : 'gagal')
                : 'terkirim',
            'body' => $data['message'],
            'external_id' => $sendResult['external_id'],
            'delivered_at' => $sendResult['success'] ? now() : null,

            /*
            |--------------------------------------------------------------------------
            | Penting:
            | read_at dibuat NULL agar pesan wali kelas dihitung sebagai pesan baru
            | di sisi orang tua sampai orang tua membuka room chat tersebut.
            |--------------------------------------------------------------------------
            */
            'read_at' => null,

            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('chat_threads')
            ->where('id', $thread->id)
            ->update([
                'assigned_to_user_id' => $user->id,
                'status' => 'open',
                'last_channel' => 'web',
                'last_message_at' => now(),
                'updated_at' => now(),
            ]);

        return redirect()
            ->route('guru.chat.show', $thread->id)
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
     |  HELPER: FORMAT MESSAGE REALTIME
     * =======================================================*/
    private function formatMessageForRealtime(object $m): array
    {
        $senderType = strtolower((string) ($m->sender_type ?? ''));

        $isWalkel = in_array($senderType, ['guru', 'wali', 'walkel'], true);
        $isAdmin = $senderType === 'admin';
        $isParent = $senderType === 'parent';

        $who = $isWalkel
            ? 'Wali Kelas'
            : ($isParent ? 'Orang Tua' : ($isAdmin ? 'Admin' : 'Pengguna'));

        $timeLabel = '-';

        if (!empty($m->created_at)) {
            $timeLabel = Carbon::parse($m->created_at)
                ->timezone('Asia/Jakarta')
                ->locale('id')
                ->translatedFormat('d M Y, H:i') . ' WIB';
        }

        return [
            'id' => (int) $m->id,
            'sender_type' => $senderType,
            'channel' => (string) ($m->channel ?? 'web'),
            'message_status' => $m->message_status ? (string) $m->message_status : null,
            'body' => (string) ($m->body ?? ''),
            'who' => $who,
            'time_label' => $timeLabel,
            'is_outgoing' => $isWalkel || $isAdmin,
        ];
    }

    /* =========================================================
     |  HELPER: WALI KELAS LOGIN + DATA GURU DARI API SIA
     * =======================================================*/
    private function getCurrentGuruOrAbort(): array
    {
        $user = Auth::user();

        if (!$user) {
            abort(403, 'Anda harus login.');
        }

        $role = strtolower((string) ($user->role ?? ''));

        if ($role !== 'walkel') {
            Log::warning('[CHAT][WALKEL] akses ditolak karena role bukan walkel', [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $role,
                'sia_user_id' => $user->sia_user_id,
            ]);

            abort(403, 'Fitur chat ini hanya dapat diakses oleh Wali Kelas.');
        }

        $guru = $this->resolveCurrentGuruFromSia($user);

        if (!$guru) {
            Log::warning('[CHAT][WALKEL] gagal resolve guru dari SIA', [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $role,
                'sia_user_id' => $user->sia_user_id,
            ]);

            abort(403, 'Data wali kelas tidak ditemukan di SIA.');
        }

        return [$user, $role, (object) $guru];
    }

    private function resolveCurrentGuruFromSia(object $user): ?array
    {
        $identifier = trim((string) ($user->sia_user_id ?? ''));

        if ($identifier !== '') {
            $guru = $this->fetchGuruByIdentifier($identifier);
            if ($guru) {
                return $guru;
            }
        }

        $email = trim((string) ($user->email ?? ''));
        if ($email !== '') {
            $guru = $this->fetchGuruByEmail($email);
            if ($guru) {
                return $guru;
            }
        }

        $name = trim((string) ($user->name ?? ''));
        if ($name !== '') {
            $guru = $this->fetchGuruByName($name);
            if ($guru) {
                return $guru;
            }
        }

        return null;
    }

    private function fetchGuruByIdentifier(string $identifier): ?array
    {
        $identifier = trim($identifier);

        if ($identifier === '') {
            return null;
        }

        try {
            if (method_exists($this->sia, 'getGuruByKey')) {
                $resp = $this->sia->getGuruByKey($identifier);

                if (
                    is_array($resp) &&
                    (($resp['success'] ?? false) === true || ($resp['status'] ?? false) === true) &&
                    !empty($resp['data']) &&
                    is_array($resp['data'])
                ) {
                    $data = $resp['data'];
                    $detailId = $data['id'] ?? null;

                    if ($detailId) {
                        $detail = $this->sia->masterGuruDetail($detailId);

                        if (
                            is_array($detail) &&
                            (($detail['success'] ?? false) === true || ($detail['status'] ?? false) === true) &&
                            !empty($detail['data']) &&
                            is_array($detail['data'])
                        ) {
                            return $detail['data'];
                        }
                    }

                    return $data;
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }

        try {
            $res = $this->sia->masterGuru($identifier);
            $items = collect($res['data'] ?? []);

            $guru = $items->first(function ($item) use ($identifier) {
                $nuptk = trim((string) ($item['nuptk'] ?? ''));
                $nip = trim((string) ($item['nip'] ?? ''));
                $email = trim((string) ($item['email'] ?? ''));
                $id = trim((string) ($item['id'] ?? ''));

                return $nuptk === $identifier
                    || $nip === $identifier
                    || $email === $identifier
                    || $id === $identifier;
            });

            if (!$guru && $items->count() === 1) {
                $guru = $items->first();
            }

            if (!$guru) {
                return null;
            }

            $id = $guru['id'] ?? null;
            if (!$id) {
                return is_array($guru) ? $guru : null;
            }

            $detail = $this->sia->masterGuruDetail($id);

            if (
                is_array($detail) &&
                (($detail['success'] ?? false) === true || ($detail['status'] ?? false) === true) &&
                !empty($detail['data']) &&
                is_array($detail['data'])
            ) {
                return $detail['data'];
            }

            return is_array($guru) ? $guru : null;
        } catch (\Throwable $e) {
            report($e);
        }

        return null;
    }

    private function fetchGuruByEmail(string $email): ?array
    {
        $email = trim(strtolower($email));

        if ($email === '') {
            return null;
        }

        try {
            $res = $this->sia->masterGuru($email);
            $items = collect($res['data'] ?? []);

            $guru = $items->first(function ($item) use ($email) {
                return strtolower(trim((string) ($item['email'] ?? ''))) === $email;
            });

            if (!$guru) {
                return null;
            }

            $id = $guru['id'] ?? null;
            if (!$id) {
                return is_array($guru) ? $guru : null;
            }

            $detail = $this->sia->masterGuruDetail($id);

            if (
                is_array($detail) &&
                (($detail['success'] ?? false) === true || ($detail['status'] ?? false) === true) &&
                !empty($detail['data']) &&
                is_array($detail['data'])
            ) {
                return $detail['data'];
            }

            return is_array($guru) ? $guru : null;
        } catch (\Throwable $e) {
            report($e);
        }

        return null;
    }

    private function fetchGuruByName(string $name): ?array
    {
        $name = trim($name);

        if ($name === '') {
            return null;
        }

        try {
            $res = $this->sia->masterGuru($name);
            $items = collect($res['data'] ?? []);

            $normalizedTarget = $this->normalizeGuruName($name);

            $guru = $items->first(function ($item) use ($normalizedTarget) {
                $candidate = $this->normalizeGuruName((string) ($item['nama'] ?? ''));
                return $candidate !== '' && $candidate === $normalizedTarget;
            });

            if (!$guru) {
                $guru = $items->first(function ($item) use ($normalizedTarget) {
                    $candidate = $this->normalizeGuruName((string) ($item['nama'] ?? ''));

                    return $candidate !== '' && (
                        str_contains($candidate, $normalizedTarget) ||
                        str_contains($normalizedTarget, $candidate)
                    );
                });
            }

            if (!$guru && $items->count() === 1) {
                $guru = $items->first();
            }

            if (!$guru) {
                return null;
            }

            $id = $guru['id'] ?? null;
            if (!$id) {
                return is_array($guru) ? $guru : null;
            }

            $detail = $this->sia->masterGuruDetail($id);

            if (
                is_array($detail) &&
                (($detail['success'] ?? false) === true || ($detail['status'] ?? false) === true) &&
                !empty($detail['data']) &&
                is_array($detail['data'])
            ) {
                return $detail['data'];
            }

            return is_array($guru) ? $guru : null;
        } catch (\Throwable $e) {
            report($e);
        }

        return null;
    }

    private function normalizeGuruName(string $name): string
    {
        $name = Str::lower(trim($name));
        $name = preg_replace('/[^a-z0-9]+/i', ' ', $name) ?? '';
        $name = preg_replace('/\s+/', ' ', $name) ?? '';

        return trim($name);
    }

    /* =========================================================
     |  HELPER: ROMBEL YANG BOLEH DIAKSES WALI KELAS
     * =======================================================*/
    private function resolveAuthorizedRombels(object $guru, string $role): Collection
    {
        if ($role !== 'walkel') {
            return collect();
        }

        try {
            $response = $this->sia->masterRombel();
            $allRombels = collect($response['data'] ?? []);
        } catch (\Throwable $e) {
            report($e);
            $allRombels = collect();
        }

        return $allRombels
            ->filter(function ($rombel) use ($guru) {
                $waliId = data_get($rombel, 'wali_kelas.id')
                    ?? data_get($rombel, 'guru.id')
                    ?? data_get($rombel, 'wali_kelas_id')
                    ?? data_get($rombel, 'guru_id');

                $aktif = data_get($rombel, 'aktif', data_get($rombel, 'status', 1));

                $isAktif = true;

                if (is_numeric($aktif)) {
                    $isAktif = (int) $aktif === 1;
                } elseif (is_string($aktif) && trim($aktif) !== '') {
                    $isAktif = in_array(strtolower(trim($aktif)), ['1', 'aktif', 'active'], true);
                }

                return (string) $waliId === (string) ($guru->id ?? null)
                    && $isAktif;
            })
            ->values()
            ->map(function ($rombel) {
                $rombel = is_array($rombel) ? $rombel : (array) $rombel;

                $rombel['rombel_label'] = $this->formatRombelLabel(
                    $rombel['tingkat'] ?? '',
                    $rombel['nama_rombel'] ?? ($rombel['nama'] ?? '')
                );

                return (object) $rombel;
            });
    }

    /* =========================================================
     |  HELPER: ORTU YANG BOLEH DIHUBUNGI WALI KELAS
     * =======================================================*/
    private function resolveAllowedParentUsers(object $guru, string $role): Collection
    {
        $authorizedRombels = $this->resolveAuthorizedRombels($guru, $role);

        if ($authorizedRombels->isEmpty()) {
            return collect();
        }

        $result = collect();

        foreach ($authorizedRombels as $rombel) {
            try {
                $anggotaRes = $this->sia->masterRombelAnggota($rombel->id);
                $anggotaList = collect($anggotaRes['data'] ?? []);
            } catch (\Throwable $e) {
                report($e);
                $anggotaList = collect();
            }

            foreach ($anggotaList as $anggota) {
                $anggota = is_array($anggota) ? $anggota : (array) $anggota;

                $nis = trim((string) ($anggota['nis'] ?? data_get($anggota, 'siswa.nis', '')));

                if ($nis === '') {
                    continue;
                }

                $userOrtu = DB::table('users')
                    ->where('role', 'ortu')
                    ->where('sia_user_id', $nis)
                    ->select('id', 'name', 'sia_user_id')
                    ->first();

                if (!$userOrtu) {
                    continue;
                }

                $detail = $this->fetchSiswaDetailByNis($nis);

                if (!$detail) {
                    continue;
                }

                $photo = $this->normalizeSiswaPhotoFields(array_merge($anggota, $detail));

                $row = (object) [
                    'user_ortu_id' => (int) $userOrtu->id,
                    'display_name' => $userOrtu->name,
                    'nis' => $detail['nis'] ?? $nis,
                    'siswa_id' => $detail['id'] ?? null,
                    'siswa_nama' => $detail['nama'] ?? ($anggota['nama'] ?? data_get($anggota, 'siswa.nama', '(data siswa tidak ditemukan)')),
                    'nisn' => $detail['nisn'] ?? ($anggota['nisn'] ?? data_get($anggota, 'siswa.nisn')),
                    'rombel_id' => $rombel->id,
                    'rombel_nama' => $rombel->rombel_label ?? $this->formatRombelLabel(
                        $rombel->tingkat ?? '',
                        $rombel->nama_rombel ?? ''
                    ),
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

                $row->wa_target = $this->pickPhone($row->no_hp_ayah, $row->no_hp_ibu);

                $result->push($row);
            }
        }

        return $result
            ->unique(fn($item) => (string) $item->user_ortu_id . '|' . (string) $item->nis)
            ->values();
    }

    /* =========================================================
     |  HELPER: THREAD LIST
     * =======================================================*/
    private function buildThreadList(Collection $allowedParents, int $guruUserId, string $q = ''): Collection
    {
        $parentIds = $allowedParents->pluck('user_ortu_id')->unique()->values()->all();

        if (empty($parentIds)) {
            return collect();
        }

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
            ->whereIn('t.owner_parent_id', $parentIds)
            ->where('t.assigned_to_user_id', $guruUserId)
            ->when($q !== '', function ($w) use ($q) {
                $w->where(function ($qq) use ($q) {
                    $qq->where('u.name', 'like', "%{$q}%")
                        ->orWhere('u.sia_user_id', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('t.last_message_at')
            ->orderByDesc('t.id')
            ->get();

        $parentMap = $allowedParents->keyBy(fn($item) => (string) $item->user_ortu_id . '|' . (string) $item->nis);

        return $threads->map(function ($row) use ($parentMap) {
            $key = (string) $row->user_parent_id . '|' . (string) $row->nis;
            $p = $parentMap->get($key);

            $row->siswa_id = $p->siswa_id ?? null;
            $row->student_name = $p->siswa_nama ?? null;
            $row->rombel_nama = $p->rombel_nama ?? null;
            $row->nama_ayah = $p->nama_ayah ?? null;
            $row->no_hp_ayah = $p->no_hp_ayah ?? null;
            $row->nama_ibu = $p->nama_ibu ?? null;
            $row->no_hp_ibu = $p->no_hp_ibu ?? null;

            $row->foto = $p->foto ?? null;
            $row->foto_url = $p->foto_url ?? null;
            $row->photo_url = $p->photo_url ?? null;
            $row->avatar = $p->avatar ?? null;
            $row->foto_siswa = $p->foto_siswa ?? null;
            $row->foto_src = $p->foto_src ?? null;
            $row->preview_foto = $p->preview_foto ?? null;
            $row->student_photo_url = $p->student_photo_url ?? ($p->foto_src ?? null);

            $parentName = $p->nama_ayah ?? $p->nama_ibu ?? $row->user_parent_name;
            $row->parent_name = $parentName ?: 'Orang Tua';

            return $row;
        });
    }

    private function bangunThreadSidebarDariThreadAktif(object $activeThread): ?object
    {
        $thread = DB::table('chat_threads as t')
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
                (
                    SELECT COUNT(*)
                    FROM chat_messages cmu
                    WHERE cmu.thread_id = t.id
                      AND cmu.sender_type = "parent"
                      AND cmu.read_at IS NULL
                ) as unread_count
            ')
            ->where('t.id', $activeThread->id)
            ->first();

        if (!$thread) {
            return null;
        }

        $thread->user_parent_id = $activeThread->owner_parent_id ?? null;
        $thread->user_parent_name = $activeThread->parent_name ?? 'Orang Tua';
        $thread->nis = $activeThread->nis ?? null;

        if (!empty($activeThread->nis)) {
            $detail = $this->fetchSiswaDetailByNis((string) $activeThread->nis);

            if ($detail) {
                $photo = $this->normalizeSiswaPhotoFields($detail);

                $thread->siswa_id = $detail['id'] ?? null;
                $thread->student_name = $detail['nama'] ?? null;
                $thread->rombel_nama = data_get($detail, 'rombel_aktif.nama_rombel')
                    ?? data_get($detail, 'rombel.nama_rombel')
                    ?? data_get($detail, 'rombel.nama')
                    ?? null;
                $thread->nama_ayah = $detail['nama_ayah'] ?? null;
                $thread->no_hp_ayah = $detail['no_hp_ayah'] ?? null;
                $thread->nama_ibu = $detail['nama_ibu'] ?? null;
                $thread->no_hp_ibu = $detail['no_hp_ibu'] ?? null;
                $thread->parent_name = $detail['nama_ayah'] ?? $detail['nama_ibu'] ?? ($activeThread->parent_name ?? 'Orang Tua');

                $thread->foto = $photo['foto'];
                $thread->foto_url = $photo['foto_url'];
                $thread->photo_url = $photo['photo_url'];
                $thread->avatar = $photo['avatar'];
                $thread->foto_siswa = $photo['foto_siswa'];
                $thread->foto_src = $photo['foto_src'];
                $thread->preview_foto = $photo['preview_foto'];
                $thread->student_photo_url = $photo['foto_src'];
            } else {
                $thread->siswa_id = null;
                $thread->student_name = null;
                $thread->rombel_nama = null;
                $thread->nama_ayah = null;
                $thread->no_hp_ayah = null;
                $thread->nama_ibu = null;
                $thread->no_hp_ibu = null;
                $thread->parent_name = $activeThread->parent_name ?? 'Orang Tua';

                $thread->foto = null;
                $thread->foto_url = null;
                $thread->photo_url = null;
                $thread->avatar = null;
                $thread->foto_siswa = null;
                $thread->foto_src = null;
                $thread->preview_foto = null;
                $thread->student_photo_url = null;
            }
        }

        return $thread;
    }

    /* =========================================================
     |  HELPER: FOTO SISWA UNTUK CHAT
     * =======================================================*/
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

    /* =========================================================
     |  UTIL
     * =======================================================*/
    private function formatRombelLabel(?string $tingkat, ?string $nama): string
    {
        $tingkat = trim((string) $tingkat);
        $nama = trim((string) $nama);

        if ($nama === '') {
            return $tingkat !== '' ? $tingkat : '-';
        }

        if ($tingkat === '') {
            return $nama;
        }

        $upperNama = strtoupper($nama);
        $upperTingkat = strtoupper($tingkat);

        if (str_starts_with($upperNama, $upperTingkat)) {
            return $nama;
        }

        return $tingkat . $nama;
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
     * KIRIM WA VIA DEVICE 2 = Guru SMADA
     */
    private function sendToWhatsappGuru(string $phone, string $message): array
    {
        $token = config('services.fonnte.guru_token');
        $deviceNumber = config('services.fonnte.guru_device_number', '6283190007144');

        if (empty($token)) {
            Log::warning('[FONNTE][WALKEL] token guru kosong', [
                'target' => $phone,
                'device' => $deviceNumber,
            ]);

            return [
                'success' => false,
                'external_id' => null,
            ];
        }

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

            Log::warning('[FONNTE][WALKEL] request gagal', [
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
            Log::warning('[FONNTE][WALKEL] send failed', [
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

        Log::info('[FONNTE][WALKEL] send success', [
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

            Log::warning('[CHAT][WALKEL] exception getSiswaByNis', [
                'nis' => $nis,
                'message' => $e->getMessage(),
            ]);

            return null;
        }

        if (
            !$basic ||
            !(($basic['status'] ?? false) === true || ($basic['success'] ?? false) === true) ||
            empty($basic['data']) ||
            !is_array($basic['data'])
        ) {
            Log::warning('[CHAT][WALKEL] gagal getSiswaByNis', [
                'nis' => $nis,
                'raw' => $basic,
            ]);

            return null;
        }

        $id = $basic['data']['id'] ?? null;

        if (!$id) {
            $basicData = $basic['data'];
            $photo = $this->normalizeSiswaPhotoFields($basicData);

            return array_merge($basicData, [
                'foto' => $photo['foto'],
                'foto_url' => $photo['foto_url'],
                'photo_url' => $photo['photo_url'],
                'avatar' => $photo['avatar'],
                'foto_siswa' => $photo['foto_siswa'],
                'foto_src' => $photo['foto_src'],
                'preview_foto' => $photo['preview_foto'],
            ]);
        }

        try {
            $detail = $this->sia->masterSiswaDetail($id);
        } catch (\Throwable $e) {
            report($e);

            Log::warning('[CHAT][WALKEL] exception masterSiswaDetail', [
                'nis' => $nis,
                'id' => $id,
                'message' => $e->getMessage(),
            ]);

            $basicData = $basic['data'];
            $photo = $this->normalizeSiswaPhotoFields($basicData);

            return array_merge($basicData, [
                'foto' => $photo['foto'],
                'foto_url' => $photo['foto_url'],
                'photo_url' => $photo['photo_url'],
                'avatar' => $photo['avatar'],
                'foto_siswa' => $photo['foto_siswa'],
                'foto_src' => $photo['foto_src'],
                'preview_foto' => $photo['preview_foto'],
            ]);
        }

        if (
            !$detail ||
            !(($detail['status'] ?? false) === true || ($detail['success'] ?? false) === true) ||
            empty($detail['data']) ||
            !is_array($detail['data'])
        ) {
            Log::warning('[CHAT][WALKEL] gagal masterSiswaDetail', [
                'nis' => $nis,
                'id' => $id,
                'raw' => $detail,
            ]);

            $basicData = $basic['data'];
            $photo = $this->normalizeSiswaPhotoFields($basicData);

            return array_merge($basicData, [
                'foto' => $photo['foto'],
                'foto_url' => $photo['foto_url'],
                'photo_url' => $photo['photo_url'],
                'avatar' => $photo['avatar'],
                'foto_siswa' => $photo['foto_siswa'],
                'foto_src' => $photo['foto_src'],
                'preview_foto' => $photo['preview_foto'],
            ]);
        }

        $merged = array_merge($basic['data'], $detail['data']);
        $photo = $this->normalizeSiswaPhotoFields($merged);

        return array_merge($merged, [
            'foto' => $photo['foto'],
            'foto_url' => $photo['foto_url'],
            'photo_url' => $photo['photo_url'],
            'avatar' => $photo['avatar'],
            'foto_siswa' => $photo['foto_siswa'],
            'foto_src' => $photo['foto_src'],
            'preview_foto' => $photo['preview_foto'],
        ]);
    }
}