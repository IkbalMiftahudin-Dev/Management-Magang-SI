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
        Schema::table('users', function (Blueprint $table) {
            $table->string('sso_sub', 100)->nullable()->unique()->after('id');
            $table->string('identifier_number', 50)->nullable()->index()->after('email');
            $table->string('role', 30)->default('Mahasiswa')->index()->after('identifier_number');
            $table->timestamp('last_sso_sync_at')->nullable()->after('remember_token');
            $table->string('password')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['sso_sub']);
            $table->dropIndex(['identifier_number']);
            $table->dropIndex(['role']);
            $table->dropColumn([
                'sso_sub',
                'identifier_number',
                'role',
                'last_sso_sync_at',
            ]);
            $table->string('password')->nullable(false)->change();
        });
    }
};
