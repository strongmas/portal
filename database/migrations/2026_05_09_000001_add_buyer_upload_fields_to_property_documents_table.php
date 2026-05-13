<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Extend document_type enum to include offer_letter
        DB::statement("ALTER TABLE `property_documents` MODIFY COLUMN `document_type` ENUM('contract','deed','survey','approval','report','receipt','other','offer_letter') NOT NULL");

        // Add status and flag_reason columns
        DB::statement("ALTER TABLE `property_documents` ADD COLUMN `status` ENUM('pending_review','flagged','verified') NOT NULL DEFAULT 'pending_review' AFTER `is_buyer_visible`");
        DB::statement("ALTER TABLE `property_documents` ADD COLUMN `flag_reason` TEXT NULL AFTER `status`");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `property_documents` DROP COLUMN `flag_reason`");
        DB::statement("ALTER TABLE `property_documents` DROP COLUMN `status`");
        DB::statement("ALTER TABLE `property_documents` MODIFY COLUMN `document_type` ENUM('contract','deed','survey','approval','report','receipt','other') NOT NULL");
    }
};
