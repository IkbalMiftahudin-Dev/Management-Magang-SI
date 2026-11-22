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
        Schema::create('logbooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pendataan_magang_id')->constrained('pendataan_magangs')->cascadeOnDelete();
            $table->string('tipe_pengisian', 20)->default('mingguan');
            $table->date('tanggal')->index();
            $table->unsignedTinyInteger('minggu_ke');
            $table->text('deskripsi_kegiatan');
            $table->string('foto_bukti_path', 255)->nullable();
            $table->string('status_validasi', 20)->default('menunggu')->index();
            $table->text('catatan_dosen')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('dosens')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logbooks');
    }
};
