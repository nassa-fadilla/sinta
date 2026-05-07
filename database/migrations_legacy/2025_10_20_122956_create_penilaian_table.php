<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('penilaian', function (Blueprint $table) {
            $table->id();

            // FK (pastikan tabel referensi sudah ada)
            $table->foreignId('tahun_ajarans_id')
                  ->nullable()
                  ->constrained('tahun_ajarans')
                  ->nullOnDelete();

            $table->foreignId('kelas_id')
                  ->constrained('kelas')
                  ->cascadeOnDelete();

            $table->foreignId('mata_pelajaran_id')
                  ->constrained('mata_pelajaran')
                  ->cascadeOnDelete();

            $table->foreignId('guru_id')
                  ->constrained('guru')
                  ->cascadeOnDelete();

            // Info penilaian
            $table->string('judul')->nullable();
            $table->enum('jenis', ['UH','PTS','PAS','Tugas','Projek','Praktik'])->default('UH');
            $table->date('tanggal')->nullable();

            $table->unsignedTinyInteger('bobot')->default(0); // 0..100
            $table->unsignedTinyInteger('kkm')->default(70);  // 0..100

            $table->boolean('is_locked')->default(false);     // jika terkunci, guru tak bisa ubah
            $table->text('catatan')->nullable();

            $table->timestamps();

            // Index bantu (nama pendek supaya tidak melebihi 64 char)
            $table->index(
                ['tahun_ajarans_id','kelas_id','mata_pelajaran_id','guru_id'],
                'idx_penilaian_ta_kelas_mapel_guru'
            );
            $table->index('tanggal', 'idx_penilaian_tgl');
            $table->index('is_locked', 'idx_penilaian_locked');
        });
    }

    public function down(): void {
        Schema::dropIfExists('penilaian');
    }
};