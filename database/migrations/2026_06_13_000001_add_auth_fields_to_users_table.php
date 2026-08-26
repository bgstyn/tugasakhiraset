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
            $table->string('username')->unique()->after('name');
            $table->string('role')->default('teknisi')->after('password'); // 'administrator' or 'teknisi'
            $table->string('position')->nullable()->after('role');
            $table->string('location')->nullable()->after('position');

            // Make email nullable since we use username for internal auth
            $table->string('email')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'role', 'position', 'location']);
            $table->string('email')->nullable(false)->change();
        });
    }
};
