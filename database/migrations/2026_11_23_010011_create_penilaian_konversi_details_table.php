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
        Schema::create('penilaian_konversi_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('konversi_mata_kuliah_id')->constrained('konversi_mata_kuliahs')->cascadeOnDelete();
            $table->foreignId('rubrik_komponen_id')->constrained('rubrik_komponens')->restrictOnDelete();
            $table->unsignedTinyInteger('bobot_persen_snapshot');
            $table->decimal('nilai', 5, 2);
            $table->timestamps();

            $table->unique(['konversi_mata_kuliah_id', 'rubrik_komponen_id'], 'pkd_konversi_rubrik_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penilaian_konversi_details');
    }
};
