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
        Schema::create('berkas_akhirs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pendataan_magang_id')->unique()->constrained('pendataan_magangs')->cascadeOnDelete();

            // 5 Dokumen Wajib
            $table->string('loa_path', 255)->nullable();
            $table->timestamp('loa_uploaded_at')->nullable();
            $table->string('rubrik_konversi_path', 255)->nullable();
            $table->timestamp('rubrik_konversi_uploaded_at')->nullable();
            $table->string('laporan_akhir_path', 255)->nullable();
            $table->timestamp('laporan_akhir_uploaded_at')->nullable();
            $table->string('draf_pks_path', 255)->nullable();
            $table->timestamp('draf_pks_uploaded_at')->nullable();
            $table->string('transkrip_instansi_path', 255)->nullable();
            $table->timestamp('transkrip_instansi_uploaded_at')->nullable();

            // 1 Dokumen Opsional (Slot 06)
            $table->string('sertifikat_path', 255)->nullable();
            $table->timestamp('sertifikat_uploaded_at')->nullable();

            $table->string('status_kelengkapan', 30)->default('belum_lengkap')->index();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('berkas_akhirs');
    }
};
