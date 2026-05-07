<?php

namespace App\Services;

use App\Models\Pengumuman;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LayananKirimPengumumanAdmin
{
    protected SiaClient $sia;

    public function __construct(SiaClient $sia)
    {
        $this->sia = $sia;
    }

    /**
     * Proses semua pengumuman aktif yang siap dikirim.
     */
    public function prosesSemua(): array
    {
        $totalPengumuman = 0;
        $totalTerkirim = 0;
        $totalDilewati = 0;
        $totalGagal = 0;

        Pengumuman::query()
            ->aktif()
            ->orderBy('id')
            ->chunkById(20, function ($items) use (&$totalPengumuman, &$totalTerkirim, &$totalDilewati, &$totalGagal) {
                foreach ($items as $item) {
                    $totalPengumuman++;

                    $hasil = $this->prosesSatuPengumuman($item);

                    $totalTerkirim += (int) ($hasil['terkirim'] ?? 0);
                    $totalDilewati += (int) ($hasil['dilewati'] ?? 0);
                    $totalGagal += (int) ($hasil['gagal'] ?? 0);
                }
            });

        return [
            'total_pengumuman' => $totalPengumuman,
            'total_terkirim' => $totalTerkirim,
            'total_dilewati' => $totalDilewati,
            'total_gagal' => $totalGagal,
        ];
    }

    /**
     * Proses satu pengumuman untuk seluruh target orang tua yang sesuai.
     */
    public function prosesSatuPengumuman(Pengumuman $pengumuman): array
    {
        $adminUser = $this->resolveAdminPengirim($pengumuman);

        if (!$adminUser) {
            Log::warning('[PENGUMUMAN][AUTO] admin pengirim tidak ditemukan', [
                'pengumuman_id' => $pengumuman->id,
            ]);

            return [
                'terkirim' => 0,
                'dilewati' => 0,
                'gagal' => 1,
            ];
        }

        $ortuList = $this->resolveTargetOrtu($pengumuman);

        $terkirim = 0;
        $dilewati = 0;
        $gagal = 0;

        foreach ($ortuList as $ortu) {
            $externalId = $this->buildExternalId((int) $pengumuman->id, (int) $ortu->id);

            $sudahAda = DB::table('chat_messages')
                ->where('external_id', $externalId)
                ->exists();

            if ($sudahAda) {
                $dilewati++;
                continue;
            }

            $detailSiswa = $this->fetchSiswaDetailByNis((string) $ortu->sia_user_id);

            if (!$detailSiswa) {
                Log::warning('[PENGUMUMAN][AUTO] detail siswa tidak ditemukan', [
                    'pengumuman_id' => $pengumuman->id,
                    'ortu_id' => $ortu->id,
                    'nis' => $ortu->sia_user_id,
                ]);

                $gagal++;
                continue;
            }

            $targetPhone = $this->pickPhone(
                $detailSiswa['no_hp_ayah'] ?? null,
                $detailSiswa['no_hp_ibu'] ?? null
            );

            if (!$targetPhone) {
                Log::warning('[PENGUMUMAN][AUTO] nomor WA orang tua tidak tersedia', [
                    'pengumuman_id' => $pengumuman->id,
                    'ortu_id' => $ortu->id,
                    'nis' => $ortu->sia_user_id,
                ]);

                $gagal++;
                continue;
            }

            $threadId = $this->findOrCreateAdminThread((int) $ortu->id, (int) $adminUser->id);
            $message = $this->buildAnnouncementMessage($pengumuman);

            $sendResult = $this->sendToWhatsappAdmin($targetPhone, $message);

            DB::table('chat_messages')->insert([
                'thread_id' => $threadId,
                'direction' => 'out',
                'channel' => 'whatsapp',
                'sender_type' => 'system',
                'sender_id' => null,
                'message_type' => 'notifikasi',
                'message_status' => $sendResult['success'] ? 'terkirim' : 'gagal',
                'body' => $message,
                'external_id' => $externalId,
                'delivered_at' => $sendResult['success'] ? now() : null,
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('chat_threads')
                ->where('id', $threadId)
                ->update([
                    'assigned_to_user_id' => $adminUser->id,
                    'status' => 'open',
                    'last_channel' => 'whatsapp',
                    'last_message_at' => now(),
                    'updated_at' => now(),
                ]);

            if ($sendResult['success']) {
                $terkirim++;
            } else {
                $gagal++;
            }
        }

        return [
            'terkirim' => $terkirim,
            'dilewati' => $dilewati,
            'gagal' => $gagal,
        ];
    }

    /**
     * Tentukan admin pengirim untuk jalur admin.
     */
    protected function resolveAdminPengirim(Pengumuman $pengumuman): ?User
    {
        if (!empty($pengumuman->created_by)) {
            $creator = User::query()
                ->where('id', $pengumuman->created_by)
                ->where('role', 'admin')
                ->first();

            if ($creator) {
                return $creator;
            }
        }

        return User::query()
            ->where('role', 'admin')
            ->orderBy('id')
            ->first();
    }

    /**
     * Tentukan target orang tua sesuai scope pengumuman.
     * Scope final yang digunakan hanya all dan tingkat.
     */
    protected function resolveTargetOrtu(Pengumuman $pengumuman)
    {
        $ortuList = User::query()
            ->where('role', 'ortu')
            ->whereNotNull('sia_user_id')
            ->select('id', 'name', 'sia_user_id')
            ->orderBy('id')
            ->get();

        if ($pengumuman->target_scope === 'all') {
            return $ortuList;
        }

        if ($pengumuman->target_scope === 'tingkat') {
            $targetTingkat = strtoupper(trim((string) ($pengumuman->target_tingkat ?? '')));

            if ($targetTingkat === '') {
                return collect();
            }

            return $ortuList->filter(function ($ortu) use ($targetTingkat) {
                $detail = $this->fetchSiswaDetailByNis((string) $ortu->sia_user_id);

                if (!$detail) {
                    return false;
                }

                $tingkat = strtoupper(trim((string) ($detail['rombel_aktif']['tingkat'] ?? '')));

                return $tingkat !== '' && $tingkat === $targetTingkat;
            })->values();
        }

        return collect();
    }

    /**
     * Cari atau buat thread admin untuk orang tua.
     */
    protected function findOrCreateAdminThread(int $ortuId, int $adminUserId): int
    {
        $thread = DB::table('chat_threads')
            ->where('owner_parent_id', $ortuId)
            ->where('assigned_to_user_id', $adminUserId)
            ->whereIn('status', ['open', 'pending'])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->first();

        if ($thread) {
            return (int) $thread->id;
        }

        return (int) DB::table('chat_threads')->insertGetId([
            'owner_parent_id' => $ortuId,
            'assigned_to_user_id' => $adminUserId,
            'status' => 'open',
            'last_channel' => 'whatsapp',
            'last_message_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Bentuk pesan notifikasi pengumuman.
     */
    protected function buildAnnouncementMessage(Pengumuman $pengumuman): string
    {
        $judul = trim((string) $pengumuman->judul);
        $isi = trim(strip_tags((string) $pengumuman->isi));
        $ringkasIsi = mb_substr($isi, 0, 250);
        $linkPengumuman = rtrim((string) config('app.url'), '/') . '/ortu/pengumuman';

        return "Assalamu'alaikum Bapak/Ibu.\n\n"
            . "Terdapat pengumuman baru dari sekolah melalui SINTA.\n\n"
            . "Judul:\n{$judul}\n\n"
            . "Ringkasan:\n{$ringkasIsi}"
            . (mb_strlen($isi) > 250 ? '...' : '')
            . "\n\nSilakan buka detail pengumuman di:\n{$linkPengumuman}\n\n"
            . "Terima kasih.";
    }

    /**
     * External ID unik agar notifikasi pengumuman tidak terkirim ganda.
     */
    protected function buildExternalId(int $pengumumanId, int $ortuId): string
    {
        return "pengumuman:{$pengumumanId}:ortu:{$ortuId}";
    }

    /**
     * Kirim WhatsApp melalui device admin.
     */
    protected function sendToWhatsappAdmin(string $phone, string $message): array
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
            Log::warning('[FONNTE][PENGUMUMAN][ADMIN] request exception', [
                'device' => $deviceNumber,
                'target' => $phone,
                'error' => $e->getMessage(),
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
            Log::warning('[FONNTE][PENGUMUMAN][ADMIN] send failed', [
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
                    $externalId = (string) ($json['id'][0] ?? null);
                } elseif (is_scalar($json['id'])) {
                    $externalId = (string) $json['id'];
                }
            } elseif (isset($json['data']['id']) && is_scalar($json['data']['id'])) {
                $externalId = (string) $json['data']['id'];
            }
        }

        return [
            'success' => true,
            'external_id' => $externalId,
        ];
    }

    /**
     * Pilih nomor HP orang tua yang tersedia.
     */
    protected function pickPhone(?string $ayah, ?string $ibu): ?string
    {
        $ayah = trim((string) ($ayah ?? ''));
        $ibu = trim((string) ($ibu ?? ''));

        $num = $ayah !== '' ? $ayah : ($ibu !== '' ? $ibu : '');

        if ($num === '') {
            return null;
        }

        return $this->normalizeMsisdn($num) ?: null;
    }

    /**
     * Normalisasi nomor HP menjadi format internasional sederhana.
     */
    protected function normalizeMsisdn(?string $s): ?string
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

    /**
     * Ambil detail siswa dari SIA berdasarkan NIS.
     */
    protected function fetchSiswaDetailByNis(string $nis): ?array
    {
        $basic = $this->sia->getSiswaByNis($nis);

        if (!$basic || ($basic['status'] ?? false) !== true) {
            Log::warning('[PENGUMUMAN][AUTO] gagal getSiswaByNis', [
                'nis' => $nis,
                'raw' => $basic,
            ]);

            return null;
        }

        $id = $basic['data']['id'] ?? null;

        if (!$id) {
            return null;
        }

        $detail = $this->sia->masterSiswaDetail($id);

        if (!$detail || ($detail['status'] ?? false) !== true) {
            Log::warning('[PENGUMUMAN][AUTO] gagal masterSiswaDetail', [
                'nis' => $nis,
                'id' => $id,
                'raw' => $detail,
            ]);

            return null;
        }

        return $detail['data'] ?? null;
    }
}