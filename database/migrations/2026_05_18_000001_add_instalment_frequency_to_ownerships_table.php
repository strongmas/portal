<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ownerships', function (Blueprint $table) {
            $table->enum('instalment_frequency', ['monthly', 'quarterly'])
                  ->default('monthly')
                  ->after('total_instalments');
        });
    }

    public function down(): void
    {
        Schema::table('ownerships', function (Blueprint $table) {
            $table->dropColumn('instalment_frequency');
        });
    }
};
