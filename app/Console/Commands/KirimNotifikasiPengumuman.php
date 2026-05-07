<?php

namespace App\Console\Commands;

use App\Services\LayananKirimPengumumanAdmin;
use Illuminate\Console\Command;

class KirimNotifikasiPengumuman extends Command
{
    protected $signature = 'pengumuman:kirim';
    protected $description = 'Kirim notifikasi pengumuman aktif kepada orang tua melalui jalur admin';

    public function handle(LayananKirimPengumumanAdmin $layanan): int
    {
        $this->info('Memulai proses notifikasi pengumuman...');
        $this->newLine();

        $hasil = $layanan->prosesSemua();

        $this->line('Ringkasan hasil:');
        $this->line('- Total pengumuman diproses : ' . ($hasil['total_pengumuman'] ?? 0));
        $this->line('- Total notifikasi terkirim : ' . ($hasil['total_terkirim'] ?? 0));
        $this->line('- Total dilewati            : ' . ($hasil['total_dilewati'] ?? 0));
        $this->line('- Total gagal               : ' . ($hasil['total_gagal'] ?? 0));

        $this->newLine();
        $this->info('Proses notifikasi pengumuman selesai.');

        return self::SUCCESS;
    }
}