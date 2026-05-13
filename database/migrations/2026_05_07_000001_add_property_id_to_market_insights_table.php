<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('market_insights', function (Blueprint $table) {
            $table->foreignId('property_id')->nullable()->after('created_by')
                  ->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('market_insights', function (Blueprint $table) {
            $table->dropForeign(['property_id']);
            $table->dropColumn('property_id');
        });
    }
};
