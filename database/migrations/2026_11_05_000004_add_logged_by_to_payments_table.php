<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Set only when a super admin logged the payment while signed in as the buyer. */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('logged_by')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('logged_by');
        });
    }
};
