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
        // 1. Add type to warehouses table (main, sub, unit)
        if (Schema::hasTable('warehouses') && !Schema::hasColumn('warehouses', 'type')) {
            Schema::table('warehouses', function (Blueprint $table) {
                $table->string('type')->default('main')->after('code'); // main, sub, unit
            });
        }

        // 2. Add warehouse_id to users table for assigning a user to a specific warehouse location
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'warehouse_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('warehouse_id')->nullable()->after('organization_id')->constrained('warehouses')->onDelete('set null');
            });
        }

        // 3. Create warehouse_stocks table for per-warehouse stock balances
        if (!Schema::hasTable('warehouse_stocks')) {
            Schema::create('warehouse_stocks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained('organizations')->onDelete('cascade');
                $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('cascade');
                $table->foreignId('inventory_item_id')->constrained('inventory_items')->onDelete('cascade');
                $table->decimal('current_stock', 12, 2)->default(0);
                $table->decimal('reorder_level', 12, 2)->default(0);
                $table->timestamps();

                $table->unique(['warehouse_id', 'inventory_item_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_stocks');

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'warehouse_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['warehouse_id']);
                $table->dropColumn('warehouse_id');
            });
        }

        if (Schema::hasTable('warehouses') && Schema::hasColumn('warehouses', 'type')) {
            Schema::table('warehouses', function (Blueprint $table) {
                $table->dropColumn('type');
            });
        }
    }
};
