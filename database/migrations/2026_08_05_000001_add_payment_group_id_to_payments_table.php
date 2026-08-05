<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Set when one bank transfer is split across several properties.
            // All rows written from the same submission share this id.
            $table->uuid('payment_group_id')->nullable()->after('user_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['payment_group_id']);
            $table->dropColumn('payment_group_id');
        });
    }
};
