<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ekskul', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->unique();               // nama ekskul unik (lintas TA, bisa diubah jika perlu)
            $table->text('deskripsi')->nullable();
            $table->enum('hari',['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'])->nullable();
            $table->time('jam_mulai')->nullable();
            $table->time('jam_selesai')->nullable();
            $table->string('lokasi', 100)->nullable();
            $table->foreignId('tahun_ajarans_id')->nullable()->constrained('tahun_ajarans')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Jika perlu cegah bentrok jadwal ekskul sama (opsional)
            // $table->unique(['hari','jam_mulai','jam_selesai','lokasi'],'ekskul_jadwal_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ekskul');
    }
};