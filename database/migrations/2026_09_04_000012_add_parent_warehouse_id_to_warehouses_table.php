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
        if (Schema::hasTable('warehouses') && !Schema::hasColumn('warehouses', 'parent_warehouse_id')) {
            Schema::table('warehouses', function (Blueprint $table) {
                $table->foreignId('parent_warehouse_id')
                    ->nullable()
                    ->after('warehouse_type_id')
                    ->constrained('warehouses')
                    ->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('warehouses') && Schema::hasColumn('warehouses', 'parent_warehouse_id')) {
            Schema::table('warehouses', function (Blueprint $table) {
                $table->dropForeign(['parent_warehouse_id']);
                $table->dropColumn('parent_warehouse_id');
            });
        }
    }
};
