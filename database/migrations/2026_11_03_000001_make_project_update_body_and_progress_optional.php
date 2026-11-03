<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('project_updates', function (Blueprint $table) {
            $table->text('body')->nullable()->change();
            $table->unsignedTinyInteger('progress_percentage')->nullable()->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_updates', function (Blueprint $table) {
            $table->text('body')->nullable(false)->change();
            $table->unsignedTinyInteger('progress_percentage')->nullable(false)->default(0)->change();
        });
    }
};
