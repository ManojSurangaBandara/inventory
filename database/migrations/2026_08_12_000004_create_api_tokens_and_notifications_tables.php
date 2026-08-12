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
        Schema::create('api_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->onDelete('cascade');
            $table->string('name');
            $table->string('token')->unique();
            $table->timestamp('last_used_at')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('role_slug')->nullable();
            $table->string('title');
            $table->text('message');
            $table->string('type')->default('info'); // info, approval_needed, rejected, completed
            $table->boolean('is_read')->default(false);
            $table->string('link_url')->nullable();
            $table->timestamps();
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->string('item_lot_number')->nullable()->after('quantity');
            $table->string('source_system')->default('manual')->after('item_lot_number');
            $table->text('rejection_reason')->nullable()->after('notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropColumn(['item_lot_number', 'source_system', 'rejection_reason']);
        });

        Schema::dropIfExists('notifications');
        Schema::dropIfExists('api_tokens');
    }
};
