<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('guru', function (Blueprint $table) {
            $table->id();
            $table->string('profil')->nullable(); // path/URL foto
            $table->string('nip')->unique()->nullable();   // ada yang belum punya nip
            $table->string('nuptk')->unique()->nullable(); // ada yang belum punya nuptk
            $table->string('nama', 150);
            $table->enum('jenis_kelamin', ['L','P']);
            $table->string('tempat_lahir', 100)->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->enum('status_kepegawaian', ['PNS','PPPK','Honorer'])->nullable();

            $table->string('no_hp', 30)->nullable();
            $table->string('email', 150)->nullable();

            $table->enum('status', ['aktif','nonaktif'])->default('aktif');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('guru');
    }
};