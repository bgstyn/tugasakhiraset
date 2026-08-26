<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->unique();
            $table->foreignId('asset_id')->constrained('assets')->onDelete('cascade');
            $table->string('reporter_name');
            $table->string('reporter_contact')->nullable();
            $table->text('description');
            $table->string('photo')->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('low');
            $table->enum('status', [
                'open',
                'assigned',
                'in_progress',
                'waiting_sparepart',
                'completed',
                'cancelled'
            ])->default('open');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_tickets');
    }
};
