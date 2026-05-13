<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_insights', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('region');
            $table->enum('category', ['pricing', 'demand', 'supply', 'rental', 'general']);
            $table->text('description');
            $table->string('value')->nullable(); // e.g. "₦850,000/sqm"
            $table->string('period')->nullable(); // e.g. "Q1 2026"
            $table->boolean('is_published')->default(false);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_insights');
    }
};
