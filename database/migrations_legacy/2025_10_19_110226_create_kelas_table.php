<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kelas', function (Blueprint $table) {
            $table->id();
            $table->string('nama');                    // contoh: X IPA 1
            $table->unsignedTinyInteger('tingkat');    // 10/11/12
            $table->string('jurusan')->nullable();     // IPA/IPS/dll

            // wali kelas (opsional) -> tabel gurus
            $table->foreignId('wali_kelas_id')->nullable()
                  ->constrained('guru')->nullOnDelete();

            // relasi wajib ke tahun ajaran
            $table->foreignId('tahun_ajaran_id')
                  ->constrained('tahun_ajarans')
                  ->cascadeOnUpdate()->restrictOnDelete();

            $table->timestamps();

            // cegah duplikat kelas di tahun ajaran sama
            $table->unique(['nama','tahun_ajaran_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kelas');
    }
};