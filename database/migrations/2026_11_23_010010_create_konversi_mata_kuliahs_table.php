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
        Schema::create('konversi_mata_kuliahs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pendataan_magang_id')->constrained('pendataan_magangs')->cascadeOnDelete();
            $table->foreignId('mata_kuliah_id')->constrained('mata_kuliahs')->restrictOnDelete();
            $table->foreignId('dosen_penilai_id')->nullable()->constrained('dosens')->nullOnDelete();
            $table->unsignedTinyInteger('sks_konversi');
            $table->string('tingkat_kesesuaian', 30)->default('sesuai');
            $table->decimal('nilai_instansi', 5, 2)->nullable();
            $table->decimal('nilai_dosen', 5, 2)->nullable();
            $table->decimal('nilai_akhir', 5, 2)->nullable();
            $table->string('nilai_huruf', 3)->nullable();
            $table->text('catatan_dosen')->nullable();
            $table->string('status_penilaian', 20)->default('belum_dinilai')->index();
            $table->boolean('is_locked')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['pendataan_magang_id', 'mata_kuliah_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('konversi_mata_kuliahs');
    }
};
