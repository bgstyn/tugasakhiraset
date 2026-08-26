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
        Schema::create('asset_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('asset_id')->nullable();
            $table->string('asset_name'); // preserve even if asset deleted
            $table->enum('type', ['fraud', 'write_off']);
            $table->string('previous_status');
            $table->text('reason');
            $table->string('document_path')->nullable(); // path to uploaded file
            $table->text('notes')->nullable();

            // Requester info (denormalized for history preservation)
            $table->string('requested_by_name');
            $table->string('requested_by_position');

            // Approval tracking
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('rejected_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamps();

            $table->foreign('asset_id')->references('id')->on('assets')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_approvals');
    }
};
