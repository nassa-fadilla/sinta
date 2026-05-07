<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ekskul_pembina', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ekskul_id')->constrained('ekskul')->cascadeOnDelete();
            $table->foreignId('guru_id')->constrained('guru')->cascadeOnDelete();
            $table->enum('jabatan',['koordinator','pembina'])->default('pembina');
            $table->timestamps();

            $table->unique(['ekskul_id','guru_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ekskul_pembina');
    }
};