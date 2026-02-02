<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        // Change model_id from unsignedBigInteger to CHAR(36) to store UUIDs
        DB::statement("ALTER TABLE `media` MODIFY `model_id` CHAR(36) NOT NULL");

        // If there is an index on model_id created by morphs, ensure it still exists
        // (MySQL keeps the index when changing type for simple cases)
    }

    public function down(): void
    {
        // Revert back to unsignedBigInteger (as created by morphs)
        DB::statement("ALTER TABLE `media` MODIFY `model_id` BIGINT UNSIGNED NOT NULL");
    }
};
