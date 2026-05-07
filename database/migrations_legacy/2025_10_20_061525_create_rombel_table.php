<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rombel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tahun_ajarans_id')->constrained('tahun_ajarans')->restrictOnDelete();
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();

            $table->unsignedSmallInteger('no_absen')->nullable(); // 1..99 (opsional)
            $table->date('tanggal_masuk')->nullable();
            $table->enum('status', ['aktif','pindah','lulus','drop'])->default('aktif');

            $table->timestamps();

            // Siswa tidak boleh dobel di tahun ajaran yang sama
            $table->unique(['tahun_ajarans_id','siswa_id'], 'uniq_tahun_siswa');

            // No absen unik dalam 1 kelas dan tahun ajaran
            $table->unique(['tahun_ajarans_id','kelas_id','no_absen'], 'uniq_absen_per_kelas');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rombel');
    }
};