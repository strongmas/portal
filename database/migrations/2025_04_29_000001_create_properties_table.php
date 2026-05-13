<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // e.g. STR-ORN-001
            $table->string('development_name');  // e.g. Orion Towers
            $table->string('address');
            $table->string('cover_image_path')->nullable();
            $table->timestamps();
        });

        // Unit types within a development (admin-only list prices)
        Schema::create('property_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('bedrooms')->unsigned()->nullable(); // null for studio/penthouse
            $table->enum('unit_type', [
                'apartment', 'terrace', 'maisonette', 'duplex',
                'penthouse', 'bungalow', 'detached', 'studio',
            ]);
            $table->bigInteger('list_price_kobo')->unsigned(); // admin-only, never shown to buyers
            $table->unsignedSmallInteger('total_units')->default(1); // stock count
            $table->string('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('ownerships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_unit_id')->nullable()->constrained('property_units')->nullOnDelete();
            $table->bigInteger('agreed_price_kobo')->unsigned(); // negotiated, buyer-visible
            $table->date('start_date')->nullable();
            $table->date('expected_completion_date')->nullable();
            $table->enum('payment_plan', ['outright', 'instalment'])->default('instalment');
            $table->integer('total_instalments')->default(24);
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->timestamps();
        });

        Schema::create('instalments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ownership_id')->constrained()->cascadeOnDelete();
            $table->integer('instalment_number');
            $table->date('due_date');
            $table->bigInteger('amount_kobo')->unsigned();
            $table->enum('status', ['upcoming', 'due', 'paid', 'overdue'])->default('upcoming');
            $table->unsignedBigInteger('paid_payment_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instalments');
        Schema::dropIfExists('ownerships');
        Schema::dropIfExists('property_units');
        Schema::dropIfExists('properties');
    }
};
