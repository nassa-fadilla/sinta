<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('chat_threads', function (Blueprint $t) {
      $t->id();
      $t->foreignId('owner_parent_id')->constrained('users');
      $t->foreignId('assigned_to_user_id')->nullable()->constrained('users');
      $t->enum('status', ['open','pending','resolved'])->default('open');
      $t->enum('last_channel', ['web','telegram'])->default('telegram');
      $t->timestamp('last_message_at')->nullable();
      $t->timestamps();
    });

    Schema::create('chat_messages', function (Blueprint $t) {
      $t->id();
      $t->foreignId('thread_id')->constrained('chat_threads')->cascadeOnDelete();
      $t->enum('direction', ['in','out']);
      $t->enum('channel', ['web','telegram'])->default('telegram');
      $t->enum('sender_type', ['parent','admin','wali','guru','bk','system'])->default('parent');
      $t->foreignId('sender_id')->nullable()->constrained('users');
      $t->text('body');
      $t->string('external_id', 100)->nullable();
      $t->timestamp('delivered_at')->nullable();
      $t->timestamp('read_at')->nullable();
      $t->timestamps();
      $t->index(['thread_id','created_at']);
    });
  }
  public function down(): void {
    Schema::dropIfExists('chat_messages');
    Schema::dropIfExists('chat_threads');
  }
};