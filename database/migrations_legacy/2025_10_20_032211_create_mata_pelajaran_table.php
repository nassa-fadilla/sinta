<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mata_pelajaran', function (Blueprint $table) {
            $table->id();
            $table->string('nama_mapel'); // contoh: Matematika, Bhs Indonesia
            $table->foreignId('guru_id')
                  ->constrained('guru')     // konsisten tanpa "s"
                  ->cascadeOnDelete();      // jika guru dihapus, mapel ikut terhapus
            $table->timestamps();

            $table->unique(['nama_mapel', 'guru_id']); // cegah duplikat guru+mapel sama
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mata_pelajaran');
    }
};