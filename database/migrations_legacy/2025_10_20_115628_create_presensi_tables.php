<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('presensi', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tahun_ajarans_id')->nullable()->constrained('tahun_ajarans')->nullOnDelete();
            $t->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $t->foreignId('mata_pelajaran_id')->constrained('mata_pelajaran')->cascadeOnDelete();
            $t->foreignId('guru_id')->constrained('guru')->cascadeOnDelete();
            $t->foreignId('jadwal_pelajaran_id')->nullable()->constrained('jadwal_pelajaran')->nullOnDelete();

            $t->date('tanggal');
            $t->time('jam_mulai')->nullable();
            $t->time('jam_selesai')->nullable();

            $t->string('ruang', 50)->nullable();
            $t->string('keterangan')->nullable();

            $t->boolean('status_locked')->default(false); // dikunci -> tidak bisa diubah guru
            $t->timestamps();

            $t->unique(['kelas_id','mata_pelajaran_id','tanggal','jam_mulai','jam_selesai'], 'presensi_unique_slot');
        });

        Schema::create('presensi_detail', function (Blueprint $t) {
            $t->id();
            $t->foreignId('presensi_id')->constrained('presensi')->cascadeOnDelete();
            $t->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();

            // status: hadir/izin/sakit/alfa/telat
            $t->enum('status', ['hadir','izin','sakit','alfa','telat'])->default('hadir');
            $t->string('catatan')->nullable();

            $t->timestamps();
            $t->unique(['presensi_id','siswa_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presensi_detail');
        Schema::dropIfExists('presensi');
    }
};