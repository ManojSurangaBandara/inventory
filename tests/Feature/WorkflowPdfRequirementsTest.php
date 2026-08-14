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

    #[\PHPUnit\Framework\Attributes\Test]
    public function external_workshop_management_system_can_create_multi_item_request_via_api()
    {
        $response = $this->withHeaders([
            'X-API-Key' => 'wms_demo_token_apex123',
            'Accept' => 'application/json',
        ])->postJson('/api/v1/item-requests', [
            'items' => [
                [
                    'sku' => 'LAP-XPS15',
                    'quantity' => 3,
                    'lot_number' => 'LOT-WMS-TEST-001',
                ],
                [
                    'sku' => 'CAB-FIBER10M',
                    'quantity' => 5,
                    'lot_number' => 'LOT-WMS-TEST-002',
                ]
            ],
            'notes' => 'Workshop Dept 4 Multi-item Requisition',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'total_items_count' => 2,
                    'total_quantity' => 8,
                    'source_system' => 'workshop_api',
                ]
            ]);

        $this->assertDatabaseHas('stock_movements', [
            'source_system' => 'workshop_api',
            'quantity' => 8,
        ]);

        $this->assertDatabaseHas('stock_movement_items', [
            'item_lot_number' => 'LOT-WMS-TEST-001',
            'quantity' => 3,
        ]);

        $this->assertDatabaseHas('stock_movement_items', [
            'item_lot_number' => 'LOT-WMS-TEST-002',
            'quantity' => 5,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function full_approval_chain_oc_qm_co_executes_and_adds_stock_for_multi_items()
    {
        $movement = StockMovement::where('reference_code', 'SM-ADD-001')->with('items.item')->first();
        $this->assertNotNull($movement);
        $this->assertEquals('oc_pending', $movement->current_state);

        $itemLaptop = InventoryItem::where('sku', 'LAP-XPS15')->first();
        $itemCable = InventoryItem::where('sku', 'CAB-FIBER10M')->first();

        $initialLaptopStock = $itemLaptop->current_stock;
        $initialCableStock = $itemCable->current_stock;

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

        // Verify Multi-Item Stock Additions
        $this->assertEquals($initialLaptopStock + 15, $itemLaptop->fresh()->current_stock);
        $this->assertEquals($initialCableStock + 10, $itemCable->fresh()->current_stock);
    }

    #[\PHPUnit\Framework\Attributes\Test]
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
