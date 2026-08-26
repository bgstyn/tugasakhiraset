<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_bundles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique(); // BDL-0001, BDL-0002, ...
            $table->unsignedBigInteger('location_id')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->foreign('location_id')->references('id')->on('locations')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_bundles');
    }
};
