<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\Organization;
use App\Models\Role;
use App\Models\StockMovement;
use App\Models\StockMovementItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowState;
use App\Models\WorkflowTransition;
use App\Services\WorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SpecializedWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $org;
    protected User $admin;
    protected User $clerk;
    protected User $storeman;
    protected Warehouse $warehouse1;
    protected Warehouse $warehouse2;
    protected InventoryItem $item;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->org = Organization::where('code', 'apex')->first();
        $this->admin = User::where('email', 'admin@apexlogistics.com')->first();
        $this->clerk = User::where('email', 'clerk@apexlogistics.com')->first();
        $this->storeman = User::where('email', 'storemen@apexlogistics.com')->first();
        $this->warehouse1 = Warehouse::where('name', 'Main Distribution Hub')->first();
        $this->warehouse2 = Warehouse::where('name', 'West Coast Distribution Center')->first();
        $this->item = InventoryItem::where('sku', 'LAP-XPS15')->first();
    }

    #[Test]
    public function can_create_specialized_workflow_definitions_for_all_movement_types()
    {
        $this->actingAs($this->admin);

        $types = [
            'StockDispatch' => 'Outbound Dispatch Approval Flow',
            'StockReceipt' => 'Inbound Quality & Receipt Flow',
            'StockTransfer' => 'Depot Transfer Protocol',
            'StockAdjustment' => 'Damage & Variance Correction Flow',
        ];

        foreach ($types as $type => $name) {
            $response = $this->post(route('workflows.store'), [
                'name' => $name,
                'entity_type' => $type,
                'description' => "Test description for {$type}",
            ]);

            $response->assertRedirect();
            $this->assertDatabaseHas('workflow_definitions', [
                'organization_id' => $this->org->id,
                'name' => $name,
                'entity_type' => $type,
                'is_active' => true,
            ]);
        }
    }

    #[Test]
    public function can_toggle_workflow_active_state()
    {
        $this->actingAs($this->admin);

        $workflow = WorkflowDefinition::where('organization_id', $this->org->id)->first();
        $this->assertTrue($workflow->is_active);

        // Deactivate
        $res = $this->post(route('workflows.toggle-active', $workflow->id));
        $res->assertRedirect();
        $workflow->refresh();
        $this->assertFalse($workflow->is_active);

        // Reactivate
        $this->post(route('workflows.toggle-active', $workflow->id));
        $workflow->refresh();
        $this->assertTrue($workflow->is_active);
    }

    #[Test]
    public function outbound_movement_uses_specialized_stock_dispatch_workflow()
    {
        $this->actingAs($this->admin);

        // Create dedicated StockDispatch workflow
        $dispatchWf = WorkflowDefinition::create([
            'organization_id' => $this->org->id,
            'name' => 'Custom Dispatch Gatepass',
            'entity_type' => 'StockDispatch',
            'is_active' => true,
        ]);

        $stDraft = WorkflowState::create([
            'organization_id' => $this->org->id,
            'workflow_definition_id' => $dispatchWf->id,
            'code' => 'dispatch_draft',
            'name' => 'Dispatch Requisition Initiated',
            'color' => 'slate',
            'is_initial' => true,
            'is_final' => false,
        ]);

        $stApproved = WorkflowState::create([
            'organization_id' => $this->org->id,
            'workflow_definition_id' => $dispatchWf->id,
            'code' => 'gatepass_approved',
            'name' => 'Gatepass Issued & Dispatched',
            'color' => 'emerald',
            'is_initial' => false,
            'is_final' => true,
        ]);

        $transition = WorkflowTransition::create([
            'organization_id' => $this->org->id,
            'workflow_definition_id' => $dispatchWf->id,
            'from_state_id' => $stDraft->id,
            'to_state_id' => $stApproved->id,
            'action_name' => 'Issue Gatepass & Dispatch',
            'allowed_roles' => ['storemen', 'oc', 'subject-clerk'],
            'requires_note' => false,
        ]);

        // Create an outbound stock request
        $response = $this->actingAs($this->clerk)->post(route('stock.store'), [
            'type' => 'outbound',
            'warehouse_id' => $this->warehouse1->id,
            'items' => [
                [
                    'inventory_item_id' => $this->item->id,
                    'quantity' => 2,
                    'item_lot_number' => 'LOT-DISPATCH-01',
                ]
            ],
            'notes' => 'Urgent field deployment requisition',
        ]);

        $response->assertRedirect(route('stock.index'));

        $movement = StockMovement::where('notes', 'Urgent field deployment requisition')->first();
        $this->assertNotNull($movement);
        $this->assertEquals('dispatch_draft', $movement->current_state);
        $this->assertEquals($dispatchWf->id, $movement->workflow_definition_id);

        // Verify available transitions
        $workflowService = app(WorkflowService::class);
        $transitions = $workflowService->getAvailableTransitions($movement, $this->storeman);
        $this->assertCount(1, $transitions);
        $this->assertEquals('Issue Gatepass & Dispatch', $transitions[0]['action_name']);

        // Execute transition
        $initialStock = $this->item->current_stock;
        $workflowService->executeTransition($movement, $transition->id, $this->storeman);
        $movement->refresh();
        $this->item->refresh();

        $this->assertEquals('gatepass_approved', $movement->current_state);
        $this->assertEquals($initialStock - 2, $this->item->current_stock);
    }

    #[Test]
    public function stock_movement_falls_back_to_general_stock_movement_workflow_when_specialized_not_present()
    {
        $workflowService = app(WorkflowService::class);

        // Requesting workflow for adjustment when no StockAdjustment workflow is active
        $wf = $workflowService->getActiveWorkflowForType('adjustment', $this->org->id);
        $this->assertNotNull($wf);
        $this->assertEquals('StockMovement', $wf->entity_type);
    }
}
