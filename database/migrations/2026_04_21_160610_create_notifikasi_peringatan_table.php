<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Jalankan migration.
     */
    public function up(): void
    {
        Schema::create('notifikasi_peringatan', function (Blueprint $table) {
            $table->id();

            // Identitas notifikasi
            $table->string('jenis_peringatan', 50);
            $table->string('kunci_peringatan', 191)->unique();

            // Identitas siswa
            $table->string('nis', 40);
            $table->unsignedBigInteger('siswa_id')->nullable();

            // Relasi ke user SINTA
            $table->unsignedBigInteger('ortu_id')->nullable();
            $table->unsignedBigInteger('walkel_id')->nullable();

            // Relasi ke fitur chat
            $table->unsignedBigInteger('thread_chat_id')->nullable();
            $table->unsignedBigInteger('pesan_chat_id')->nullable();

            // Status kirim notifikasi
            $table->enum('status_kirim', ['menunggu', 'terkirim', 'gagal', 'dilewati'])
                ->default('menunggu');

            // Snapshot data saat notifikasi diproses
            $table->json('snapshot_data')->nullable();

            // Waktu notifikasi diproses / dikirim
            $table->timestamp('waktu_kirim')->nullable();

            $table->timestamps();

            // Index
            $table->index(['jenis_peringatan', 'nis'], 'idx_notif_jenis_nis');
            $table->index(['ortu_id', 'walkel_id'], 'idx_notif_ortu_walkel');
            $table->index('thread_chat_id', 'idx_notif_thread_chat');
            $table->index('pesan_chat_id', 'idx_notif_pesan_chat');
        });
    }

    /**
     * Rollback migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifikasi_peringatan');
    }
};