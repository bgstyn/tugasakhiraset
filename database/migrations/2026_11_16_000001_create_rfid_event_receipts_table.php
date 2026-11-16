<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rfid_event_receipts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('fingerprint', 64);
            $table->unsignedSmallInteger('status')->nullable();
            $table->text('body')->nullable();
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfid_event_receipts');
    }
};
