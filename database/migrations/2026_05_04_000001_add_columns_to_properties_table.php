<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->decimal('lat', 10, 7)->nullable()->after('cover_image_path');
            $table->decimal('lng', 10, 7)->nullable()->after('lat');
            $table->boolean('is_off_plan')->default(true)->after('lng');
            $table->enum('project_status', ['planning', 'foundation', 'construction', 'finishing', 'completed'])
                  ->default('planning')->after('is_off_plan');
            $table->text('description')->nullable()->after('project_status');
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn(['lat', 'lng', 'is_off_plan', 'project_status', 'description']);
        });
    }
};
