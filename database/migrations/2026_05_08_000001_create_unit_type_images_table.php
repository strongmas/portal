<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_type_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_unit_id')->constrained('property_units')->cascadeOnDelete();
            $table->enum('room_type', ['exterior', 'bedroom', 'kitchen', 'bathroom', 'living_area']);
            $table->string('path');
            $table->string('caption')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_type_images');
    }
};
