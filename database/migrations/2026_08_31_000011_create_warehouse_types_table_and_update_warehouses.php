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
        // 1. Create warehouse_types table
        Schema::create('warehouse_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->onDelete('cascade');
            $table->string('name'); // e.g. Main Warehouse (Central), Unit Workshop, Cold Storage Depot
            $table->string('code'); // e.g. MAIN, SUB, UNIT, COLD
            $table->string('color')->default('emerald'); // emerald, blue, amber, purple, rose, cyan, indigo
            $table->string('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
        });

        // 2. Add warehouse_type_id to warehouses table
        Schema::table('warehouses', function (Blueprint $table) {
            $table->foreignId('warehouse_type_id')->nullable()->after('type')->constrained('warehouse_types')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropForeign(['warehouse_type_id']);
            $table->dropColumn('warehouse_type_id');
        });

        Schema::dropIfExists('warehouse_types');
    }
};
