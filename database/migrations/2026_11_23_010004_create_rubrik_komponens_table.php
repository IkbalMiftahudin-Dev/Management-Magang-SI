<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rubrik_komponens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mata_kuliah_id')->constrained('mata_kuliahs')->cascadeOnDelete();
            $table->string('nama_komponen', 150);
            $table->string('deskripsi', 255)->nullable();
            $table->unsignedTinyInteger('bobot_persen');
            $table->unsignedTinyInteger('urutan')->default(1);
            $table->timestamps();

            $table->index(['mata_kuliah_id', 'urutan']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rubrik_komponens');
    }
};
