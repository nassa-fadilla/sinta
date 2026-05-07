<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('siswa', function (Blueprint $table) {
            $table->id();
            $table->string('nisn')->unique();
            $table->string('nis')->unique();
            $table->string('nama');
            $table->enum('jenis_kelamin', ['L','P']);
            $table->string('tempat_lahir');
            $table->date('tanggal_lahir');
            $table->enum('agama', ['Islam','Katolik', 'Protestan', 'Hindu', 'Buddha', 'Konghucu'])->nullable();

            // alamat & kontak
            $table->text('alamat')->nullable();
            $table->string('no_hp', 30)->nullable();
            $table->string('email')->nullable();

            // info lain
            $table->enum ('jalur_penerimaan', ['Afirmasi','Mutasi', 'Prestasi', 'Domisili Khusus', 'Domisili Reguler'])->nullable();
            $table->enum('kebutuhan_khusus', ['Iya','Tidak'])->nullable();
            $table->year('tahun_masuk')->nullable();
            $table->enum('status', ['aktif','nonaktif'])->default('aktif');

            // relasi (nullable dulu, FK bisa ditambahkan nanti)
            $table->unsignedBigInteger('orangtua_id')->nullable();
            $table->unsignedBigInteger('kelas_id')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('siswa');
    }
};
