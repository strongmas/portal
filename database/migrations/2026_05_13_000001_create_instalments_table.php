<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('instalments')) {
            return;
        }

        Schema::create('instalments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ownership_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('instalment_number');
            $table->date('due_date');
            $table->bigInteger('amount_kobo')->unsigned();
            $table->enum('status', ['upcoming', 'due', 'overdue', 'paid'])->default('upcoming');
            $table->foreignId('paid_payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instalments');
    }
};
