<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            // Skip if the index already exists (safe to re-run)
            if (collect(DB::select("SHOW INDEX FROM payments WHERE Key_name = 'payments_ownership_reference_unique'"))->isNotEmpty()) {
                return;
            }

            // Remove duplicate non-null (ownership_id, reference) pairs before adding the constraint,
            // keeping the earliest payment in each group.
            DB::statement("
                DELETE p1 FROM payments p1
                INNER JOIN payments p2
                  ON  p1.ownership_id = p2.ownership_id
                  AND p1.reference    = p2.reference
                  AND p1.reference IS NOT NULL
                  AND p1.id > p2.id
            ");
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->unique(['ownership_id', 'reference'], 'payments_ownership_reference_unique');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique('payments_ownership_reference_unique');
        });
    }
};
