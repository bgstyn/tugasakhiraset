<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_bundle_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('asset_bundle_id');
            $table->unsignedBigInteger('asset_id');
            $table->timestamps();

            $table->unique(['asset_bundle_id', 'asset_id']);

            $table->foreign('asset_bundle_id')->references('id')->on('asset_bundles')->onDelete('cascade');
            $table->foreign('asset_id')->references('id')->on('assets')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_bundle_items');
    }
};
