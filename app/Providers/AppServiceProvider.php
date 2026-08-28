<?php

namespace App\Providers;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        try {
            // Ensure warehouses.type exists
            if (Schema::hasTable('warehouses') && !Schema::hasColumn('warehouses', 'type')) {
                Schema::table('warehouses', function (Blueprint $table) {
                    $table->string('type')->default('main')->after('code');
                });
            }

            // Ensure users.warehouse_id exists
            if (Schema::hasTable('users') && !Schema::hasColumn('users', 'warehouse_id')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->foreignId('warehouse_id')->nullable()->after('organization_id')->constrained('warehouses')->onDelete('set null');
                });
            }

            // Ensure warehouse_stocks table exists
            if (Schema::hasTable('warehouses') && Schema::hasTable('inventory_items') && !Schema::hasTable('warehouse_stocks')) {
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
        } catch (\Throwable $e) {
            // Safe fallback during command executions or offline databases
        }
    }
}
