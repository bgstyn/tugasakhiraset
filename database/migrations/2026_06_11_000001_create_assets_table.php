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
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('asset_id')->unique();
            $table->string('government_inventory_number')->unique();
            $table->string('serial_number')->nullable()->unique();
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->text('specification')->nullable();
            $table->string('photo')->nullable();
            $table->string('building')->nullable();
            $table->string('floor')->nullable();
            $table->string('room')->nullable();
            $table->string('current_user')->nullable();
            $table->integer('year');
            $table->enum('status', [
                'standby',
                'digunakan',
                'maintenance',
                'rusak',
                'fraud',
                'write_off',
                'pending_fraud_approval',
                'pending_write_off_approval'
            ])->default('standby');
            $table->string('rfid_uid')->nullable()->unique();
            $table->enum('rfid_status', ['belum_terdaftar', 'aktif', 'nonaktif'])->default('belum_terdaftar');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
