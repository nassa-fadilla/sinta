<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('penilaian_detail', function (Blueprint $table) {
            $table->id();

            $table->foreignId('penilaian_id')
                  ->constrained('penilaian')
                  ->cascadeOnDelete();

            $table->foreignId('siswa_id')
                  ->constrained('siswa')
                  ->cascadeOnDelete();

            // Nilai per siswa
            $table->decimal('nilai', 5, 2)->nullable(); // contoh 87.50
            $table->string('predikat', 5)->nullable();  // A/B/C/D/E
            $table->string('catatan', 255)->nullable();

            $table->timestamps();

            // Satu siswa hanya satu baris per penilaian
            $table->unique(['penilaian_id','siswa_id'], 'uq_penilaian_siswa');

            // Index bantu
            $table->index('siswa_id', 'idx_pd_siswa');
        });
    }

    public function down(): void {
        Schema::dropIfExists('penilaian_detail');
    }
};
