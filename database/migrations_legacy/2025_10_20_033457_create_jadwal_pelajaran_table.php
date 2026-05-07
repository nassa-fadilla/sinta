<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jadwal_pelajaran', function (Blueprint $table) {
            $table->id();

            // Kelas, Mapel, Guru, Tahun Ajaran
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $table->foreignId('mata_pelajaran_id')->constrained('mata_pelajaran')->cascadeOnDelete();
            $table->foreignId('guru_id')->constrained('guru')->cascadeOnDelete();
            // catatan: tahun_ajarans sudah existing (pakai "s" sesuai histori)
            $table->foreignId('tahun_ajarans_id')->constrained('tahun_ajarans')->restrictOnDelete();

            // Waktu & meta
            $table->enum('hari', ['Senin','Selasa','Rabu','Kamis','Jumat']);
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->time('durasi');
            $table->string('ruang', 50)->nullable();

            $table->timestamps();

            // Cegah duplikat persis slot di kelas yang sama
            $table->unique(['kelas_id', 'hari', 'jam_mulai', 'jam_selesai', 'durasi', 'tahun_ajarans_id'], 'uniq_slot_kelas');

            // Index bantu
            $table->index(['guru_id', 'hari']);
            $table->index(['tahun_ajarans_id', 'kelas_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jadwal_pelajaran');
    }
};