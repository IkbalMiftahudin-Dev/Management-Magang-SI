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
        Schema::create('pendataan_magangs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mahasiswa_id')->constrained('mahasiswas')->cascadeOnDelete();
            $table->foreignId('dosen_pembimbing_id')->nullable()->constrained('dosens')->nullOnDelete();
            $table->foreignId('dosen_kunjung_id')->nullable()->constrained('dosens')->nullOnDelete();
            $table->string('semester_akademik', 40)->nullable()->index();

            // Step 1: Data Instansi
            $table->string('nama_instansi', 150);
            $table->string('posisi', 150);
            $table->text('alamat_instansi');
            $table->string('kontak_instansi', 150);

            // Step 2: Program, Periode, & LOA Wajib
            $table->string('jenis_program', 30)->nullable()->index();
            $table->string('mode_kegiatan', 20)->nullable();
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->string('loa_path', 255)->nullable();
            $table->timestamp('loa_uploaded_at')->nullable();

            // Progres Wizard & Verifikasi Prodi
            $table->unsignedTinyInteger('current_step')->default(1);
            $table->boolean('pernyataan_disetujui')->default(false);
            $table->string('status_pendataan', 30)->default('draft')->index();
            $table->text('catatan_verifikasi')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pendataan_magangs');
    }
};
