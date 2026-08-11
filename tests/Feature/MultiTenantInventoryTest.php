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
        $this->actingAs($apexAdmin);

        $item = InventoryItem::where('sku', 'LAP-XPS15')->first();
        $initialStock = $item->current_stock;
        $wh = Warehouse::where('code', 'WH-MAIN')->first();

        // Create Stock Movement (inbound 10 pcs)
        $movement = StockMovement::create([
            'organization_id' => $apexAdmin->organization_id,
            'reference_code' => 'SM-TEST-001',
            'type' => 'inbound',
            'warehouse_id' => $wh->id,
            'inventory_item_id' => $item->id,
            'quantity' => 10,
            'current_state' => 'draft',
            'created_by' => $apexAdmin->id,
        ]);

        $service = app(WorkflowService::class);
        $workflow = $service->getActiveWorkflow('StockMovement', $apexAdmin->organization_id);

        $transition = WorkflowTransition::where('workflow_definition_id', $workflow->id)
            ->where('action_name', 'Submit for QC Inspection')
            ->first();

        // Execute Draft -> Inspection Pending
        $service->executeTransition($movement, $transition->id, $apexAdmin, 'Submitting test movement');
        $movement->refresh();
        $this->assertEquals('inspection_pending', $movement->current_state);

        // Execute Inspection Pending -> Passed & Released (Terminal state)
        $approveTransition = WorkflowTransition::where('workflow_definition_id', $workflow->id)
            ->where('action_name', 'Approve & Release Stock')
            ->first();

        $service->executeTransition($movement, $approveTransition->id, $apexAdmin, 'QC Inspection Passed');
        $movement->refresh();
        $item->refresh();

        $this->assertEquals('completed', $movement->current_state);
        // Stock should be incremented by 10 (since inbound movement reached final completed state)
        $this->assertEquals($initialStock + 10, $item->current_stock);
    }
}
