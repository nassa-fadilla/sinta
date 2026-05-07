<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('anggota_ekskul', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ekskul_id')->constrained('ekskul')->cascadeOnDelete();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->foreignId('tahun_ajarans_id')->nullable()->constrained('tahun_ajarans')->nullOnDelete();
            $table->enum('jabatan', ['anggota', 'ketua', 'wakil'])->default('anggota');
            $table->date('bergabung_at')->nullable();
            $table->date('keluar_at')->nullable();
            $table->string('keterangan', 150)->nullable();
            $table->timestamps();

            // Keanggotaan per TA (boleh join lagi di TA berikutnya)
            $table->unique(['ekskul_id', 'siswa_id', 'tahun_ajarans_id'], 'anggota_unique_ta');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anggota_ekskul');
    }
};