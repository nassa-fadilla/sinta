<?php

namespace App\Services;

use App\Models\NotifikasiPeringatan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LayananKirimPeringatan
{
    protected SiaClient $sia;

    public function __construct(SiaClient $sia)
    {
        $this->sia = $sia;
    }

    /**
     * Proses satu data peringatan.
     */
    public function kirim(array $peringatan): array
    {
        $kunciPeringatan = trim((string) ($peringatan['kunci_peringatan'] ?? ''));
        $jenisPeringatan = trim((string) ($peringatan['jenis_peringatan'] ?? ''));
        $nis = trim((string) ($peringatan['nis'] ?? ''));

        if ($kunciPeringatan === '' || $jenisPeringatan === '' || $nis === '') {
            return [
                'berhasil' => false,
                'status' => 'dilewati',
                'pesan' => 'Data peringatan tidak lengkap.',
            ];
        }

        $sudahAda = NotifikasiPeringatan::query()
            ->where('kunci_peringatan', $kunciPeringatan)
            ->exists();

        if ($sudahAda) {
            return [
                'berhasil' => false,
                'status' => 'dilewati',
                'pesan' => 'Peringatan dengan kunci yang sama sudah pernah diproses.',
            ];
        }

        $logNotifikasi = NotifikasiPeringatan::create([
            'jenis_peringatan' => $jenisPeringatan,
            'kunci_peringatan' => $kunciPeringatan,
            'nis' => $nis,
            'siswa_id' => $peringatan['siswa_id'] ?? null,
            'ortu_id' => $peringatan['ortu_id'] ?? null,
            'walkel_id' => null,
            'thread_chat_id' => null,
            'pesan_chat_id' => null,
            'status_kirim' => 'menunggu',
            'snapshot_data' => $peringatan['snapshot_data'] ?? null,
            'waktu_kirim' => null,
        ]);

        try {
            $ortu = $this->temukanOrtu($peringatan, $nis);
            if (!$ortu) {
                return $this->tandaiSelesai($logNotifikasi, 'dilewati', 'Akun orang tua tidak ditemukan.');
            }

            $detailSiswa = $this->ambilDetailSiswa($nis);
            if (!$detailSiswa) {
                return $this->tandaiSelesai($logNotifikasi, 'dilewati', 'Detail siswa tidak ditemukan dari SIA.', [
                    'ortu_id' => $ortu->id,
                ]);
            }

            $walkel = $this->temukanWalkelDariSiswa($detailSiswa);
            if (!$walkel) {
                return $this->tandaiSelesai($logNotifikasi, 'dilewati', 'Akun wali kelas tidak ditemukan.', [
                    'ortu_id' => $ortu->id,
                ]);
            }

            $nomorTujuan = $this->pilihNomorOrtu(
                $detailSiswa['no_hp_ayah'] ?? null,
                $detailSiswa['no_hp_ibu'] ?? null
            );

            if (!$nomorTujuan) {
                return $this->tandaiSelesai($logNotifikasi, 'gagal', 'Nomor WhatsApp orang tua tidak tersedia.', [
                    'ortu_id' => $ortu->id,
                    'walkel_id' => $walkel->id,
                ]);
            }

            $threadId = $this->temukanAtauBuatThread($ortu->id, $walkel->id);
            $isiPesan = $this->susunPesanPeringatan($peringatan);

            $hasilKirim = $this->kirimWhatsappWalkel($nomorTujuan, $isiPesan);

            $pesanChatId = DB::table('chat_messages')->insertGetId([
                'thread_id' => $threadId,
                'direction' => 'out',
                'channel' => 'whatsapp',
                'sender_type' => 'system',
                'sender_id' => null,
                'message_type' => 'notifikasi',
                'message_status' => $hasilKirim['berhasil'] ? 'terkirim' : 'gagal',
                'body' => $isiPesan,
                'external_id' => $hasilKirim['external_id'],
                'delivered_at' => $hasilKirim['berhasil'] ? now() : null,
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('chat_threads')
                ->where('id', $threadId)
                ->update([
                    'assigned_to_user_id' => $walkel->id,
                    'status' => 'open',
                    'last_channel' => 'whatsapp',
                    'last_message_at' => now(),
                    'updated_at' => now(),
                ]);

            $statusAkhir = $hasilKirim['berhasil'] ? 'terkirim' : 'gagal';

            $logNotifikasi->update([
                'ortu_id' => $ortu->id,
                'walkel_id' => $walkel->id,
                'thread_chat_id' => $threadId,
                'pesan_chat_id' => $pesanChatId,
                'status_kirim' => $statusAkhir,
                'waktu_kirim' => now(),
            ]);

            return [
                'berhasil' => $hasilKirim['berhasil'],
                'status' => $statusAkhir,
                'pesan' => $hasilKirim['berhasil']
                    ? 'Notifikasi berhasil dikirim.'
                    : 'Notifikasi tercatat, tetapi gagal dikirim ke WhatsApp.',
                'notifikasi_peringatan_id' => $logNotifikasi->id,
                'thread_chat_id' => $threadId,
                'pesan_chat_id' => $pesanChatId,
            ];
        } catch (\Throwable $e) {
            Log::error('[PERINGATAN][KIRIM] Terjadi kesalahan', [
                'kunci_peringatan' => $kunciPeringatan,
                'pesan_error' => $e->getMessage(),
            ]);

            $logNotifikasi->update([
                'status_kirim' => 'gagal',
                'waktu_kirim' => now(),
            ]);

            return [
                'berhasil' => false,
                'status' => 'gagal',
                'pesan' => 'Terjadi kesalahan saat memproses notifikasi.',
            ];
        }
    }

    /**
     * Cari akun orang tua dari data peringatan atau NIS.
     */
    protected function temukanOrtu(array $peringatan, string $nis): ?User
    {
        if (!empty($peringatan['ortu_id'])) {
            return User::query()
                ->where('id', $peringatan['ortu_id'])
                ->where('role', 'ortu')
                ->first();
        }

        return User::query()
            ->where('role', 'ortu')
            ->where('sia_user_id', $nis)
            ->first();
    }

    /**
     * Ambil detail siswa lengkap dari SIA.
     */
    protected function ambilDetailSiswa(string $nis): ?array
    {
        $basic = $this->sia->getSiswaByNis($nis);

        if (!$basic || ($basic['status'] ?? false) !== true) {
            return null;
        }

        $id = $basic['data']['id'] ?? null;
        if (!$id) {
            return null;
        }

        $detail = $this->sia->masterSiswaDetail($id);

        if (!$detail || ($detail['status'] ?? false) !== true) {
            return null;
        }

        return $detail['data'] ?? null;
    }

    /**
     * Cari user walkel dari rombel aktif siswa.
     */
    protected function temukanWalkelDariSiswa(array $detailSiswa): ?User
    {
        $rombelAktif = $detailSiswa['rombel_aktif'] ?? null;
        $rombelId = $rombelAktif['id'] ?? null;

        if (!$rombelId) {
            return null;
        }

        $detailRombel = $this->sia->masterRombelDetail($rombelId);

        if (!$detailRombel || ($detailRombel['status'] ?? false) !== true) {
            return null;
        }

        $dataRombel = $detailRombel['data'] ?? [];

        $waliKelas = $dataRombel['wali_kelas'] ?? [];
        $kandidatIdentifier = array_filter([
            trim((string) ($waliKelas['nuptk'] ?? '')),
            trim((string) ($waliKelas['nip'] ?? '')),
            trim((string) ($waliKelas['id'] ?? '')),
            trim((string) ($dataRombel['wali_kelas_id'] ?? '')),
            trim((string) ($dataRombel['guru_id'] ?? '')),
        ]);

        if (empty($kandidatIdentifier)) {
            return null;
        }

        return User::query()
            ->where('role', 'walkel')
            ->where(function ($q) use ($kandidatIdentifier) {
                foreach ($kandidatIdentifier as $identifier) {
                    $q->orWhere('sia_user_id', $identifier);
                }
            })
            ->first();
    }

    /**
     * Pilih nomor ayah dulu, jika kosong pakai ibu.
     */
    protected function pilihNomorOrtu(?string $nomorAyah, ?string $nomorIbu): ?string
    {
        $nomorAyah = $this->normalisasiNomor($nomorAyah);
        $nomorIbu = $this->normalisasiNomor($nomorIbu);

        return $nomorAyah ?: ($nomorIbu ?: null);
    }

    /**
     * Cari thread aktif atau buat thread baru parent-walkel.
     */
    protected function temukanAtauBuatThread(int $ortuId, int $walkelId): int
    {
        $thread = DB::table('chat_threads')
            ->where('owner_parent_id', $ortuId)
            ->where('assigned_to_user_id', $walkelId)
            ->whereIn('status', ['open', 'pending'])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->first();

        if ($thread) {
            return (int) $thread->id;
        }

        return (int) DB::table('chat_threads')->insertGetId([
            'owner_parent_id' => $ortuId,
            'assigned_to_user_id' => $walkelId,
            'status' => 'open',
            'last_channel' => 'whatsapp',
            'last_message_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Susun isi pesan berdasarkan jenis peringatan.
     */
    protected function susunPesanPeringatan(array $peringatan): string
    {
        $jenis = $peringatan['jenis_peringatan'] ?? '';
        $namaSiswa = $peringatan['nama_siswa'] ?? 'Siswa';
        $rombelNama = $peringatan['rombel_nama'] ?? '-';
        $appUrl = rtrim((string) config('app.url'), '/');

        if ($jenis === 'nilai_di_bawah_kkm') {
            $namaMapel = $peringatan['nama_mapel'] ?? '-';
            $nilaiAkhir = $peringatan['nilai_akhir'] ?? '-';
            $kkm = $peringatan['kkm'] ?? '-';
            $linkNilai = $appUrl . '/ortu/nilai';

            return "Assalamu'alaikum Bapak/Ibu.\n\n"
                . "SINTA mendeteksi bahwa nilai ananda {$namaSiswa} pada mata pelajaran {$namaMapel} berada di bawah KKM.\n\n"
                . "Detail:\n"
                . "- Siswa: {$namaSiswa}\n"
                . "- Kelas: {$rombelNama}\n"
                . "- Mata Pelajaran: {$namaMapel}\n"
                . "- Nilai: {$nilaiAkhir}\n"
                . "- KKM: {$kkm}\n\n"
                . "Silakan lihat detail melalui SINTA:\n{$linkNilai}\n\n"
                . "Terima kasih.";
        }

        if ($jenis === 'alpa_lebih_dari_5') {
            $totalAlpa = $peringatan['total_alpa'] ?? '-';
            $linkKehadiran = $appUrl . '/ortu/kehadiran';

            return "Assalamu'alaikum Bapak/Ibu.\n\n"
                . "SINTA mendeteksi bahwa jumlah ketidakhadiran tanpa keterangan (alpa) ananda {$namaSiswa} telah melebihi batas pemantauan.\n\n"
                . "Detail:\n"
                . "- Siswa: {$namaSiswa}\n"
                . "- Kelas: {$rombelNama}\n"
                . "- Total Alpa: {$totalAlpa}\n\n"
                . "Silakan lihat detail melalui SINTA:\n{$linkKehadiran}\n\n"
                . "Terima kasih.";
        }

        return "Assalamu'alaikum Bapak/Ibu.\n\n"
            . "SINTA mendeteksi adanya informasi yang perlu diperhatikan terkait ananda {$namaSiswa}.\n\n"
            . "Silakan buka portal SINTA untuk melihat detail lebih lanjut.\n\n"
            . "Terima kasih.";
    }

    /**
     * Kirim WA melalui device walkel.
     */
    protected function kirimWhatsappWalkel(string $nomorTujuan, string $isiPesan): array
    {
        $token = config('services.fonnte.guru_token');
        $deviceNumber = config('services.fonnte.guru_device_number', '6283190007144');

        $payload = [
            'target' => $nomorTujuan,
            'message' => $isiPesan,
            'device' => $deviceNumber,
        ];

        $response = Http::withHeaders([
            'Authorization' => $token,
        ])->post('https://api.fonnte.com/send', $payload);

        $json = null;

        try {
            $json = $response->json();
        } catch (\Throwable $e) {
            $json = null;
        }

        if (!$response->successful()) {
            Log::warning('[PERINGATAN][WA] Gagal kirim WhatsApp', [
                'target' => $nomorTujuan,
                'device' => $deviceNumber,
                'status_http' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'berhasil' => false,
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
            'berhasil' => true,
            'external_id' => $externalId,
        ];
    }

    /**
     * Normalisasi nomor HP ke format 62xxxxxxxxxx.
     */
    protected function normalisasiNomor(?string $nomor): ?string
    {
        $angka = preg_replace('/\D+/', '', (string) $nomor);

        if ($angka === '') {
            return null;
        }

        if (str_starts_with($angka, '0')) {
            return '62' . substr($angka, 1);
        }

        if (str_starts_with($angka, '8')) {
            return '62' . $angka;
        }

        return $angka;
    }

    /**
     * Update log notifikasi saat proses berakhir lebih awal.
     */
    protected function tandaiSelesai(NotifikasiPeringatan $logNotifikasi, string $status, string $pesan, array $tambahan = []): array
    {
        $logNotifikasi->update(array_merge([
            'status_kirim' => $status,
            'waktu_kirim' => now(),
        ], $tambahan));

        return [
            'berhasil' => false,
            'status' => $status,
            'pesan' => $pesan,
            'notifikasi_peringatan_id' => $logNotifikasi->id,
        ];
    }
}