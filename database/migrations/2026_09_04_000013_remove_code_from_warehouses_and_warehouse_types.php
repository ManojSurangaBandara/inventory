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
        if (Schema::hasTable('warehouse_types') && Schema::hasColumn('warehouse_types', 'code')) {
            Schema::table('warehouse_types', function (Blueprint $table) {
                // Ensure foreign key index exists before dropping composite unique index
                $table->index('organization_id');
                $table->dropUnique(['organization_id', 'code']);
                $table->dropColumn('code');
            });
        }

        if (Schema::hasTable('warehouses') && Schema::hasColumn('warehouses', 'code')) {
            Schema::table('warehouses', function (Blueprint $table) {
                $table->dropColumn('code');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('warehouses') && !Schema::hasColumn('warehouses', 'code')) {
            Schema::table('warehouses', function (Blueprint $table) {
                $table->string('code')->nullable()->after('name');
            });
        }

        if (Schema::hasTable('warehouse_types') && !Schema::hasColumn('warehouse_types', 'code')) {
            Schema::table('warehouse_types', function (Blueprint $table) {
                $table->string('code')->nullable()->after('name');
                $table->unique(['organization_id', 'code']);
            });
        }
    }
};
