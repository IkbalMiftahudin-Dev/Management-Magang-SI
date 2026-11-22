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
        Schema::create('lowongan_magangs', function (Blueprint $table) {
            $table->id();
            $table->string('judul_lowongan', 180);
            $table->string('nama_instansi', 150);
            $table->string('posisi_bidang', 120);
            $table->string('lokasi_mode', 120);
            $table->string('sumber_lowongan', 30)->index();
            $table->string('kontak', 150);
            $table->text('deskripsi');
            $table->text('persyaratan')->nullable();
            $table->string('logo_path', 255)->nullable();
            $table->unsignedInteger('jumlah_pelamar')->default(0);
            $table->date('tanggal_posting')->index();
            $table->date('batas_waktu')->nullable();
            $table->string('status', 20)->default('aktif')->index();
            $table->foreignId('dosen_penanggung_jawab_id')->nullable()->constrained('dosens')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lowongan_magangs');
    }
};
