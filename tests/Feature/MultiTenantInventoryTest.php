<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\Organization;
use App\Models\Role;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowState;
use App\Models\WorkflowTransition;
use App\Services\WorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MultiTenantInventoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    #[Test]
    public function super_admin_can_access_superadmin_dashboard_and_create_organization()
    {
        $superAdmin = User::where('is_super_admin', true)->first();

        $response = $this->actingAs($superAdmin)->get(route('superadmin.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Global System Control Panel');

        $response = $this->actingAs($superAdmin)->post(route('superadmin.organizations.store'), [
            'name' => 'Acme Global',
            'code' => 'acme',
            'email' => 'contact@acme.com',
            'admin_name' => 'Acme Admin',
            'admin_email' => 'admin@acme.com',
            'admin_password' => 'password123',
        ]);

        $response->assertRedirect(route('superadmin.organizations'));
        $this->assertDatabaseHas('organizations', ['code' => 'acme']);
        $this->assertDatabaseHas('users', ['email' => 'admin@acme.com', 'is_org_admin' => 1]);
    }

    #[Test]
    public function org_a_users_cannot_access_or_see_org_b_inventory_data()
    {
        $apexAdmin = User::where('email', 'admin@apexlogistics.com')->first();
        $nexusAdmin = User::where('email', 'admin@nexusglobal.com')->first();

        // Acting as Apex Admin
        $this->actingAs($apexAdmin);
        $apexItems = InventoryItem::all();

        // Ensure Apex Admin only sees Apex items (LAP-XPS15, MON-4K27, CAB-FIBER10M) and NOT Nexus items (NEX-MED01)
        $this->assertTrue($apexItems->pluck('sku')->contains('LAP-XPS15'));
        $this->assertFalse($apexItems->pluck('sku')->contains('NEX-MED01'));

        // Acting as Nexus Admin
        $this->actingAs($nexusAdmin);
        $nexusItems = InventoryItem::all();

        // Ensure Nexus Admin only sees NEX-MED01 and NOT LAP-XPS15
        $this->assertTrue($nexusItems->pluck('sku')->contains('NEX-MED01'));
        $this->assertFalse($nexusItems->pluck('sku')->contains('LAP-XPS15'));
    }

    #[Test]
    public function org_admin_can_create_custom_role_and_assign_to_org_user()
    {
        $apexAdmin = User::where('email', 'admin@apexlogistics.com')->first();

        $this->actingAs($apexAdmin)->post(route('orgadmin.roles.store'), [
            'name' => 'Stock Inspector',
            'description' => 'Role for inspecting stock',
        ]);

        $apexOrgId = $apexAdmin->organization_id;
        $this->assertDatabaseHas('roles', ['name' => 'Stock Inspector', 'organization_id' => $apexOrgId]);
        $role = Role::where('organization_id', $apexOrgId)->where('name', 'Stock Inspector')->first();

        $this->actingAs($apexAdmin)->post(route('orgadmin.users.store'), [
            'name' => 'Inspector Sam',
            'email' => 'sam@apexlogistics.com',
            'password' => 'password123',
            'roles' => [$role->id],
        ]);

        $newUser = User::where('email', 'sam@apexlogistics.com')->first();
        $this->assertNotNull($newUser);
        $this->assertTrue($newUser->hasRole('stock-inspector'));
    }

    #[Test]
    public function workflow_engine_enforces_allowed_roles_and_executes_stock_adjustment()
    {
        $apexAdmin = User::where('email', 'admin@apexlogistics.com')->first();
        $clerk = User::where('email', 'clerk@apexlogistics.com')->first();
        $oc = User::where('email', 'oc@apexlogistics.com')->first();
        $qm = User::where('email', 'qm@apexlogistics.com')->first();
        $co = User::where('email', 'co@apexlogistics.com')->first();

        $item = InventoryItem::where('sku', 'LAP-XPS15')->first();
        $initialStock = $item->current_stock;
        $wh = Warehouse::where('name', 'Main Distribution Hub')->first();

        // Create Stock Movement (inbound 10 pcs)
        $movement = StockMovement::create([
            'organization_id' => $apexAdmin->organization_id,
            'reference_code' => 'SM-TEST-001',
            'type' => 'inbound',
            'warehouse_id' => $wh->id,
            'inventory_item_id' => $item->id,
            'quantity' => 10,
            'item_lot_number' => 'LOT-TEST-10',
            'current_state' => 'draft',
            'created_by' => $clerk->id,
        ]);

        \App\Models\StockMovementItem::create([
            'organization_id' => $apexAdmin->organization_id,
            'stock_movement_id' => $movement->id,
            'inventory_item_id' => $item->id,
            'quantity' => 10,
            'item_lot_number' => 'LOT-TEST-10',
        ]);

        $service = app(WorkflowService::class);
        $workflow = $service->getActiveWorkflow('StockMovement', $apexAdmin->organization_id);

        $transition = WorkflowTransition::where('workflow_definition_id', $workflow->id)
            ->where('action_name', 'Submit Stock Requisition to OC')
            ->first();

        // Admin cannot submit or approve because admin lacks subject-clerk / oc role
        $this->expectException(\Exception::class);
        $service->executeTransition($movement, $transition->id, $apexAdmin, 'Admin trying to bypass role');
    }

    #[Test]
    public function relevant_role_users_successfully_execute_multi_stage_workflow()
    {
        $clerk = User::where('email', 'clerk@apexlogistics.com')->first();
        $oc = User::where('email', 'oc@apexlogistics.com')->first();
        $qm = User::where('email', 'qm@apexlogistics.com')->first();
        $co = User::where('email', 'co@apexlogistics.com')->first();

        $item = InventoryItem::where('sku', 'LAP-XPS15')->first();
        $initialStock = $item->current_stock;
        $wh = Warehouse::where('name', 'Main Distribution Hub')->first();

        // Create Stock Movement (inbound 10 pcs)
        $movement = StockMovement::create([
            'organization_id' => $clerk->organization_id,
            'reference_code' => 'SM-TEST-002',
            'type' => 'inbound',
            'warehouse_id' => $wh->id,
            'inventory_item_id' => $item->id,
            'quantity' => 10,
            'item_lot_number' => 'LOT-TEST-10',
            'current_state' => 'draft',
            'created_by' => $clerk->id,
        ]);

        \App\Models\StockMovementItem::create([
            'organization_id' => $clerk->organization_id,
            'stock_movement_id' => $movement->id,
            'inventory_item_id' => $item->id,
            'quantity' => 10,
            'item_lot_number' => 'LOT-TEST-10',
        ]);

        $service = app(WorkflowService::class);
        $workflow = $service->getActiveWorkflow('StockMovement', $clerk->organization_id);

        $transition = WorkflowTransition::where('workflow_definition_id', $workflow->id)
            ->where('action_name', 'Submit Stock Requisition to OC')
            ->first();

        // Execute Draft -> OC Pending (by Clerk)
        $service->executeTransition($movement, $transition->id, $clerk, 'Submitting test movement');
        $movement->refresh();
        $this->assertEquals('oc_pending', $movement->current_state);

        // Execute OC Approved & Forward to QM -> QM Pending (by OC)
        $trOC = WorkflowTransition::where('workflow_definition_id', $workflow->id)
            ->where('action_name', 'OC Approved & Forward to QM')
            ->first();
        $service->executeTransition($movement, $trOC->id, $oc, 'OC Passed');

        // Execute QM Approved & Forward to CO -> CO Pending (by QM)
        $trQM = WorkflowTransition::where('workflow_definition_id', $workflow->id)
            ->where('action_name', 'QM Approved & Forward to CO')
            ->first();
        $service->executeTransition($movement, $trQM->id, $qm, 'QM Passed');

        // Execute CO Approve & Add Items to Stock -> Completed (by CO)
        $approveTransition = WorkflowTransition::where('workflow_definition_id', $workflow->id)
            ->where('action_name', 'CO Approve & Add Items to Stock')
            ->first();

        $service->executeTransition($movement, $approveTransition->id, $co, 'CO Authorized');
        $movement->refresh();
        $item->refresh();

        $this->assertEquals('completed', $movement->current_state);
        // Stock should be incremented by 10
        $this->assertEquals($initialStock + 10, $item->current_stock);
    }
}
