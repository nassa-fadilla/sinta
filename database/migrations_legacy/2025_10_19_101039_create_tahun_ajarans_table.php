<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tahun_ajarans', function (Blueprint $table) {
            $table->id();
            $table->string('nama');                           // contoh: 2025/2026
            $table->enum('semester', ['Ganjil', 'Genap']);    // semester
            $table->boolean('is_aktif')->default(false);      // penanda aktif
            $table->timestamps();

            // kombinasi unik agar "nama + semester" tidak dobel
            $table->unique(['nama', 'semester']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tahun_ajarans');
    }
};