<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->onDelete('cascade');
            $table->foreignId('ticket_id')->nullable()->constrained('maintenance_tickets')->onDelete('set null');
            $table->foreignId('technician_id')->constrained('users')->onDelete('cascade');
            $table->text('diagnosis');
            $table->text('cause');
            $table->text('action_taken');
            $table->text('spareparts')->nullable();
            $table->string('photo_before')->nullable();
            $table->string('photo_after')->nullable();
            $table->text('notes')->nullable();
            $table->date('maintenance_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_logs');
    }
};
