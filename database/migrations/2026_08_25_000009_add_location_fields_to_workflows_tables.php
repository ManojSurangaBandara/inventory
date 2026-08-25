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
        Schema::table('workflow_definitions', function (Blueprint $table) {
            $table->foreignId('warehouse_id')->nullable()->after('entity_type')->constrained('warehouses')->onDelete('set null');
        });

        Schema::table('workflow_states', function (Blueprint $table) {
            $table->string('location')->nullable()->after('color');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workflow_definitions', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropColumn('warehouse_id');
        });

        Schema::table('workflow_states', function (Blueprint $table) {
            $table->dropColumn('location');
        });
    }
};
