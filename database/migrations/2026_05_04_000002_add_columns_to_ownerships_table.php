<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ownerships', function (Blueprint $table) {
            $table->date('purchase_date')->nullable()->after('status');
            $table->bigInteger('current_value_kobo')->unsigned()->nullable()->after('purchase_date');
        });
    }

    public function down(): void
    {
        Schema::table('ownerships', function (Blueprint $table) {
            $table->dropColumn(['purchase_date', 'current_value_kobo']);
        });
    }
};
