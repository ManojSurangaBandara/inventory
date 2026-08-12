<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\InventoryItem;
use App\Models\Notification;
use App\Models\Organization;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowState;
use App\Models\WorkflowTransition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowPdfRequirementsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /** @test */
    public function external_workshop_management_system_can_create_item_request_via_api()
    {
        $response = $this->withHeaders([
            'X-API-Key' => 'wms_demo_token_apex123',
            'Accept' => 'application/json',
        ])->postJson('/api/v1/item-requests', [
            'sku' => 'LAP-XPS15',
            'quantity' => 3,
            'lot_number' => 'LOT-WMS-TEST-001',
            'notes' => 'Workshop Dept 4 Requisition',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'item_sku' => 'LAP-XPS15',
                    'quantity' => 3,
                    'lot_number' => 'LOT-WMS-TEST-001',
                    'source_system' => 'workshop_api',
                ]
            ]);

        $this->assertDatabaseHas('stock_movements', [
            'item_lot_number' => 'LOT-WMS-TEST-001',
            'source_system' => 'workshop_api',
            'quantity' => 3,
        ]);
    }

    /** @test */
    public function full_approval_chain_oc_qm_co_executes_and_adds_stock()
    {
        $movement = StockMovement::where('reference_code', 'SM-ADD-001')->first();
        $this->assertNotNull($movement);
        $this->assertEquals('oc_pending', $movement->current_state);

        $initialStock = $movement->item->current_stock;

        // Step 1: OC Approves
        $userOC = User::where('email', 'oc@apexlogistics.com')->first();
        $trOC = WorkflowTransition::where('action_name', 'OC Approved & Forward to QM')->first();

        $this->actingAs($userOC)
            ->post("/stock/{$movement->id}/transition", [
                'transition_id' => $trOC->id,
                'notes' => 'OC verified item lot details.',
            ])
            ->assertRedirect();

        $movement->refresh();
        $this->assertEquals('qm_pending', $movement->current_state);

        // Step 2: QM Approves
        $userQM = User::where('email', 'qm@apexlogistics.com')->first();
        $trQM = WorkflowTransition::where('action_name', 'QM Approved & Forward to CO')->first();

        $this->actingAs($userQM)
            ->post("/stock/{$movement->id}/transition", [
                'transition_id' => $trQM->id,
                'notes' => 'QM quality check passed.',
            ])
            ->assertRedirect();

        $movement->refresh();
        $this->assertEquals('co_pending', $movement->current_state);

        // Step 3: CO Final Approves -> Adds Items to Stock
        $userCO = User::where('email', 'co@apexlogistics.com')->first();
        $trCO = WorkflowTransition::where('action_name', 'CO Approve & Add Items to Stock')->first();

        $this->actingAs($userCO)
            ->post("/stock/{$movement->id}/transition", [
                'transition_id' => $trCO->id,
                'notes' => 'CO final authorization granted.',
            ])
            ->assertRedirect();

        $movement->refresh();
        $this->assertEquals('completed', $movement->current_state);

        // Verify Stock Addition
        $this->assertEquals($initialStock + 15, $movement->item->fresh()->current_stock);
    }

    /** @test */
    public function rejection_by_authority_locks_request_and_creates_notification()
    {
        $movement = StockMovement::where('reference_code', 'SM-ADD-001')->first();

        $userOC = User::where('email', 'oc@apexlogistics.com')->first();
        $trReject = WorkflowTransition::where('action_name', 'OC Reject Information')->first();

        $this->actingAs($userOC)
            ->post("/stock/{$movement->id}/transition", [
                'transition_id' => $trReject->id,
                'notes' => 'Specification discrepancy in batch lot number.',
            ])
            ->assertRedirect();

        $movement->refresh();
        $this->assertEquals('rejected', $movement->current_state);
        $this->assertEquals('Specification discrepancy in batch lot number.', $movement->rejection_reason);

        // Verify rejection notification created
        $this->assertDatabaseHas('notifications', [
            'user_id' => $movement->created_by,
            'type' => 'rejected',
        ]);
    }
}
