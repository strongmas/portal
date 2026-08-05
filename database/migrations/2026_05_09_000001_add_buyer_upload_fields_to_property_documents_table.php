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
            // Extend document_type enum to include offer_letter
            DB::statement("ALTER TABLE `property_documents` MODIFY COLUMN `document_type` ENUM('contract','deed','survey','approval','report','receipt','other','offer_letter') NOT NULL");

            // Add status and flag_reason columns
            DB::statement("ALTER TABLE `property_documents` ADD COLUMN `status` ENUM('pending_review','flagged','verified') NOT NULL DEFAULT 'pending_review' AFTER `is_buyer_visible`");
            DB::statement("ALTER TABLE `property_documents` ADD COLUMN `flag_reason` TEXT NULL AFTER `status`");

            return;
        }

        // Portable equivalent (sqlite test runs): enums become strings.
        Schema::table('property_documents', function (Blueprint $table) {
            $table->string('status')->default('pending_review');
            $table->text('flag_reason')->nullable();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `property_documents` DROP COLUMN `flag_reason`");
            DB::statement("ALTER TABLE `property_documents` DROP COLUMN `status`");
            DB::statement("ALTER TABLE `property_documents` MODIFY COLUMN `document_type` ENUM('contract','deed','survey','approval','report','receipt','other') NOT NULL");

            return;
        }

        Schema::table('property_documents', function (Blueprint $table) {
            $table->dropColumn(['status', 'flag_reason']);
        });
    }
};
