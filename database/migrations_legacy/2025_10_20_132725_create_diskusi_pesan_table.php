<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('diskusi_pesan', function (Blueprint $table) {
            $table->id();

            $table->foreignId('diskusi_id')->constrained('diskusi')->cascadeOnDelete();

            // siapa yang mengirim
            $table->enum('sender_role', ['admin','walikelas','guru','orangtua']);
            $table->unsignedBigInteger('sender_id')->nullable(); // users.id untuk admin/wk/guru (opsional)
            $table->text('pesan');

            // attachment sederhana (bisa dikembangkan ke tabel terpisah)
            $table->json('attachments')->nullable();

            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['diskusi_id','created_at']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('diskusi_pesan');
    }
};