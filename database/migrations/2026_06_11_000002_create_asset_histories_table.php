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
        Schema::create('asset_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('asset_id')->nullable(); // nullable in case asset is deleted but history remains
            $table->string('asset_name'); // to keep the asset name even if deleted
            $table->string('action'); // 'create', 'update', 'delete'
            $table->string('changed_by_name');
            $table->string('changed_by_position');
            $table->string('changed_by_location');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->timestamps();

            // Setup foreign key index but don't restrict cascade on delete so history is preserved
            $table->foreign('asset_id')->references('id')->on('assets')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_histories');
    }
};
