<?php

namespace App\Console\Commands;

use App\Services\LayananEvaluasiPeringatan;
use App\Services\LayananKirimPeringatan;
use Illuminate\Console\Command;

class KirimNotifikasiPeringatan extends Command
{
    /**
     * Nama command di terminal.
     */
    protected $signature = 'peringatan:kirim';

    /**
     * Deskripsi command.
     */
    protected $description = 'Evaluasi dan kirim notifikasi peringatan siswa dari data SIA';

    /**
     * Jalankan command.
     */
    public function handle(
        LayananEvaluasiPeringatan $layananEvaluasiPeringatan,
        LayananKirimPeringatan $layananKirimPeringatan
    ): int {
        $this->info('Memulai proses evaluasi notifikasi peringatan...');
        $this->newLine();

        $hasilEvaluasi = $layananEvaluasiPeringatan->ambilSemuaPeringatan();

        $daftarPeringatanNilai = collect($hasilEvaluasi['peringatan_nilai'] ?? []);
        $daftarPeringatanAlpa = collect($hasilEvaluasi['peringatan_alpa'] ?? []);

        $this->line('Hasil evaluasi:');
        $this->line('- Peringatan nilai ditemukan: ' . $daftarPeringatanNilai->count());
        $this->line('- Peringatan alpa ditemukan: ' . $daftarPeringatanAlpa->count());
        $this->newLine();

        $totalDiproses = 0;
        $totalTerkirim = 0;
        $totalGagal = 0;
        $totalDilewati = 0;

        $semuaPeringatan = $daftarPeringatanNilai
            ->concat($daftarPeringatanAlpa)
            ->values();

        if ($semuaPeringatan->isEmpty()) {
            $this->info('Tidak ada peringatan yang perlu diproses.');
            return self::SUCCESS;
        }

        foreach ($semuaPeringatan as $index => $peringatan) {
            $nomorUrut = $index + 1;
            $jenis = $peringatan['jenis_peringatan'] ?? '-';
            $namaSiswa = $peringatan['nama_siswa'] ?? '-';
            $nis = $peringatan['nis'] ?? '-';

            $this->line("[{$nomorUrut}] Memproses {$jenis} untuk {$namaSiswa} (NIS: {$nis}) ...");

            $hasilKirim = $layananKirimPeringatan->kirim($peringatan);

            $status = $hasilKirim['status'] ?? 'gagal';
            $pesan = $hasilKirim['pesan'] ?? '-';

            $totalDiproses++;

            if ($status === 'terkirim') {
                $totalTerkirim++;
                $this->info("    BERHASIL: {$pesan}");
            } elseif ($status === 'dilewati') {
                $totalDilewati++;
                $this->warn("    DILEWATI: {$pesan}");
            } else {
                $totalGagal++;
                $this->error("    GAGAL: {$pesan}");
            }
        }

        $this->newLine();
        $this->info('Proses selesai.');
        $this->line('Ringkasan:');
        $this->line('- Total diproses : ' . $totalDiproses);
        $this->line('- Terkirim       : ' . $totalTerkirim);
        $this->line('- Gagal          : ' . $totalGagal);
        $this->line('- Dilewati       : ' . $totalDilewati);

        return self::SUCCESS;
    }
}