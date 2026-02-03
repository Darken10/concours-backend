<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            // For SQLite, we need to drop the index first, then recreate the table
            Schema::table('media', function (Blueprint $table) {
                $table->dropIndex('media_model_type_model_id_index');
            });

            Schema::table('media', function (Blueprint $table) {
                $table->string('model_id_temp', 56)->nullable()->after('model_id');
            });

            DB::statement('UPDATE media SET model_id_temp = CAST(model_id AS TEXT)');

            Schema::table('media', function (Blueprint $table) {
                $table->dropColumn('model_id');
            });

            Schema::table('media', function (Blueprint $table) {
                $table->renameColumn('model_id_temp', 'model_id');
            });

            // Recreate the index
            Schema::table('media', function (Blueprint $table) {
                $table->index(['model_type', 'model_id'], 'media_model_type_model_id_index');
            });
        } else {
            // MySQL/PostgreSQL can use ALTER MODIFY/ALTER COLUMN
            DB::statement("ALTER TABLE `media` MODIFY `model_id` CHAR(56) NOT NULL");
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            // For SQLite, drop the index first
            Schema::table('media', function (Blueprint $table) {
                $table->dropIndex('media_model_type_model_id_index');
            });

            Schema::table('media', function (Blueprint $table) {
                $table->unsignedBigInteger('model_id_temp')->nullable()->after('model_id');
            });

            DB::statement('UPDATE media SET model_id_temp = CAST(model_id AS INTEGER)');

            Schema::table('media', function (Blueprint $table) {
                $table->dropColumn('model_id');
            });

            Schema::table('media', function (Blueprint $table) {
                $table->renameColumn('model_id_temp', 'model_id');
            });

            // Recreate the index
            Schema::table('media', function (Blueprint $table) {
                $table->index(['model_type', 'model_id'], 'media_model_type_model_id_index');
            });
        } else {
            // MySQL/PostgreSQL
            DB::statement("ALTER TABLE `media` MODIFY `model_id` BIGINT UNSIGNED NOT NULL");
        }
    }
};
