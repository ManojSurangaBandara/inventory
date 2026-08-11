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
        Schema::create('workflow_definitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->onDelete('cascade');
            $table->string('name');
            $table->string('entity_type'); // e.g. StockMovement, PurchaseOrder
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('workflow_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_definition_id')->constrained('workflow_definitions')->onDelete('cascade');
            $table->string('code');
            $table->string('name');
            $table->string('color')->default('slate'); // slate, emerald, amber, rose, indigo, blue, purple
            $table->boolean('is_initial')->default(false);
            $table->boolean('is_final')->default(false);
            $table->timestamps();
        });

        Schema::create('workflow_transitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_definition_id')->constrained('workflow_definitions')->onDelete('cascade');
            $table->foreignId('from_state_id')->constrained('workflow_states')->onDelete('cascade');
            $table->foreignId('to_state_id')->constrained('workflow_states')->onDelete('cascade');
            $table->string('action_name');
            $table->json('allowed_roles')->nullable(); // JSON array of role slugs or IDs
            $table->boolean('requires_note')->default(false);
            $table->timestamps();
        });

        Schema::create('workflow_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->onDelete('cascade');
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->string('from_state')->nullable();
            $table->string('to_state');
            $table->string('action');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_logs');
        Schema::dropIfExists('workflow_transitions');
        Schema::dropIfExists('workflow_states');
        Schema::dropIfExists('workflow_definitions');
    }
};
