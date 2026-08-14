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
        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('organization_id')->constrained('categories')->onDelete('cascade');
            $table->tinyInteger('level')->default(1)->after('parent_id'); // 1 = Category 1, 2 = Category 2, 3 = Category 3, 4 = Category 4
            $table->string('code')->nullable()->after('level');
        });

        Schema::table('inventory_items', function (Blueprint $table) {
            $table->foreignId('category_2_id')->nullable()->after('category_id')->constrained('categories')->onDelete('set null');
            $table->foreignId('category_3_id')->nullable()->after('category_2_id')->constrained('categories')->onDelete('set null');
            $table->foreignId('category_4_id')->nullable()->after('category_3_id')->constrained('categories')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropForeign(['category_4_id']);
            $table->dropForeign(['category_3_id']);
            $table->dropForeign(['category_2_id']);
            $table->dropColumn(['category_4_id', 'category_3_id', 'category_2_id']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['parent_id', 'level', 'code']);
        });
    }
};
