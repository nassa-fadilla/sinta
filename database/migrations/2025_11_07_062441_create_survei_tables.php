<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Tabel utama: survei
        Schema::create('survei', function (Blueprint $table) {
            $table->id();
            $table->string('judul', 150);
            $table->text('deskripsi')->nullable();
            $table->dateTime('mulai_at')->nullable();
            $table->dateTime('akhir_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        // Tabel pertanyaan survei
        Schema::create('survei_pertanyaan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survei_id')->constrained('survei')->cascadeOnDelete();
            $table->text('pertanyaan');
            $table->enum('tipe', ['text', 'pilihan', 'skala'])->default('text');
            $table->json('pilihan')->nullable(); // untuk opsi jawaban (jika tipe = pilihan)
            $table->integer('urutan')->default(1);
            $table->timestamps();
        });

        // Tabel respon survei
        Schema::create('survei_respon', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survei_id')->constrained('survei')->cascadeOnDelete();
            $table->foreignId('ortu_user_id')->constrained('users')->cascadeOnDelete();
            $table->json('jawaban'); // { "1": "Ya", "2": "Tidak", ... }
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survei_respon');
        Schema::dropIfExists('survei_pertanyaan');
        Schema::dropIfExists('survei');
    }
};
