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
        Schema::create('dokumen_sks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pendataan_magang_id')->constrained('pendataan_magangs')->cascadeOnDelete();
            $table->string('jenis_sk', 30)->index();
            $table->string('nomor_sk', 100)->unique();
            $table->string('file_path', 255);
            $table->string('checksum_sha256', 64)->nullable();
            $table->string('status_pengesahan', 20)->default('draft')->index();
            $table->date('tanggal_diterbitkan')->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(['pendataan_magang_id', 'jenis_sk']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dokumen_sks');
    }
};
