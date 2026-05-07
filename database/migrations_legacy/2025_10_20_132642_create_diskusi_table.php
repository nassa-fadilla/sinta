<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('diskusi', function (Blueprint $table) {
            $table->id();

            // Thread terkait siswa (orang tua mewakili siswa)
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();

            // (opsional) filter per tahun ajaran
            $table->foreignId('tahun_ajarans_id')->nullable()
                  ->constrained('tahun_ajarans')->nullOnDelete();

            // Metadata thread
            $table->string('judul', 150);
            $table->enum('status', ['open','resolved','closed'])->default('open');
            $table->boolean('is_pinned')->default(false);

            // untuk notifikasi WA (opsional)
            $table->string('wa_number', 30)->nullable();

            // siapa yang buat (admin optional)
            $table->foreignId('created_by')->nullable()
                  ->constrained('users')->nullOnDelete();

            // waktu pesan terakhir (buat sorting)
            $table->timestamp('last_message_at')->nullable();

            $table->timestamps();

            $table->index(['status','is_pinned']);
            $table->index(['tahun_ajarans_id','siswa_id']);
            $table->index('last_message_at');
        });
    }

    public function down(): void {
        Schema::dropIfExists('diskusi');
    }
};