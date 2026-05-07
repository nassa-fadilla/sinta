<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->enum('message_type', ['teks', 'notifikasi'])
                ->default('teks')
                ->after('sender_id');

            $table->enum('message_status', ['menunggu', 'terkirim', 'diterima', 'dibaca', 'gagal'])
                ->default('menunggu')
                ->after('message_type');
        });
    }

    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropColumn(['message_type', 'message_status']);
        });
    }
};