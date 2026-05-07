<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pengumuman', function (Blueprint $table) {
            $table->id();

            $table->string('judul', 150);
            $table->text('isi');

            // Target: all / kelas / tingkat
            $table->enum('target_scope', ['all','kelas','tingkat'])->default('all');

            // Jika 'tingkat' → 10/11/12 (atau X/XI/XII sesuai data kelas.tingkat kamu)
            $table->unsignedTinyInteger('target_tingkat')->nullable();

            // Jadwal tayang
            $table->timestamp('publish_at')->nullable();
            $table->timestamp('expire_at')->nullable();

            // Status
            $table->boolean('is_active')->default(true);

            // Relasi umum
            $table->foreignId('tahun_ajarans_id')->nullable()->constrained('tahun_ajarans')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });

        // Pivot (multi kelas untuk scope=kelas)
        Schema::create('pengumuman_kelas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengumuman_id')->constrained('pengumuman')->cascadeOnDelete();
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $table->unique(['pengumuman_id','kelas_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengumuman_kelas');
        Schema::dropIfExists('pengumuman');
    }
};