<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::table('users', function (Blueprint $t) {
      if (!Schema::hasColumn('users','siswa_id')) {
        $t->unsignedBigInteger('siswa_id')->nullable()->unique()->after('id');
        $t->foreign('siswa_id')->references('id')->on('siswa')->nullOnDelete();
      }
      // pastikan field role & activation_code sudah ada dari step sebelumnya;
      // kalau belum, aktifkan baris berikut:
      // if (!Schema::hasColumn('users','role')) $t->string('role',20)->default('ortu')->index();
      // if (!Schema::hasColumn('users','activation_code')) $t->string('activation_code',20)->nullable()->unique();
    });
  }

  public function down(): void {
    Schema::table('users', function (Blueprint $t) {
      if (Schema::hasColumn('users','siswa_id')) {
        $t->dropForeign(['siswa_id']);
        $t->dropColumn('siswa_id');
      }
    });
  }
};