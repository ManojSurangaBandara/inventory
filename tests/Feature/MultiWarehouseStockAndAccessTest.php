<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\Organization;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowState;
use App\Models\WorkflowTransition;
use App\Services\WorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiWarehouseStockAndAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_multi_warehouse_types_per_warehouse_stock_and_user_scoping(): void
    {
        $org = Organization::create(['name' => 'Apex Logistics', 'code' => 'APEX', 'status' => 'active']);
        
        $admin = User::create([
            'name' => 'Org Admin',
            'email' => 'admin@apexlogistics.com',
            'password' => bcrypt('password'),
            'organization_id' => $org->id,
            'is_org_admin' => true,
        ]);

        // 1. Create 3 warehouses with types: main, sub, unit
        $mainWH = Warehouse::create([
            'organization_id' => $org->id,
            'name' => 'Central Depot',
            'type' => 'main',
            'location' => 'Colombo Harbor',
        ]);

        $subWH = Warehouse::create([
            'organization_id' => $org->id,
            'name' => 'Regional Sub-Depot',
            'type' => 'sub',
            'location' => 'Kandy Sector',
        ]);

        $unitWH = Warehouse::create([
            'organization_id' => $org->id,
            'name' => 'Workshop Unit #1',
            'type' => 'unit',
            'location' => 'Field Garage',
        ]);

        $this->assertEquals('Main Warehouse (Central)', $mainWH->type_label);
        $this->assertEquals('Sub Warehouse (Regional)', $subWH->type_label);
        $this->assertEquals('Unit Warehouse (Workshop/Field)', $unitWH->type_label);

        // 2. Create Subject Clerk user assigned to Workshop Unit #1
        $clerk = User::create([
            'name' => 'Unit Subject Clerk',
            'email' => 'clerk@apexlogistics.com',
            'password' => bcrypt('password'),
            'organization_id' => $org->id,
            'warehouse_id' => $unitWH->id,
            'is_org_admin' => false,
        ]);

        $this->assertTrue($clerk->isWarehouseScoped());
        $this->assertEquals($unitWH->id, $clerk->warehouse_id);

        // 3. Create Inventory Item
        $cat = Category::create([
            'organization_id' => $org->id,
            'level' => 1,
            'name' => 'Spares',
            'code' => 'SPARE',
        ]);

        $item = InventoryItem::create([
            'organization_id' => $org->id,
            'category_id' => $cat->id,
            'sku' => 'SP-001',
            'name' => 'Engine Piston',
            'unit' => 'pcs',
            'unit_cost' => 2500.00,
            'reorder_level' => 5,
            'current_stock' => 0,
        ]);

        // 4. Setup Simple Inbound Workflow
        $wf = WorkflowDefinition::create([
            'organization_id' => $org->id,
            'name' => 'Standard Inbound Flow',
            'entity_type' => 'StockReceipt',
            'is_active' => true,
        ]);

        $draftState = WorkflowState::create([
            'organization_id' => $org->id,
            'workflow_definition_id' => $wf->id,
            'code' => 'draft',
            'name' => 'Draft',
            'is_initial' => true,
        ]);

        $completedState = WorkflowState::create([
            'organization_id' => $org->id,
            'workflow_definition_id' => $wf->id,
            'code' => 'completed',
            'name' => 'Completed & Received',
            'is_final' => true,
        ]);

        $transition = WorkflowTransition::create([
            'organization_id' => $org->id,
            'workflow_definition_id' => $wf->id,
            'from_state_id' => $draftState->id,
            'to_state_id' => $completedState->id,
            'action_name' => 'Receive Stock',
        ]);

        // 5. Execute Inbound Movement to Central Depot (Qty 50)
        $this->actingAs($admin);
        $movementRes = $this->post(route('stock.store'), [
            'type' => 'inbound',
            'warehouse_id' => $mainWH->id,
            'items' => [
                ['inventory_item_id' => $item->id, 'quantity' => 50, 'lot_number' => 'LOT-01'],
            ],
        ]);

        $movement = \App\Models\StockMovement::first();
        $this->assertNotNull($movement);

        // Execute transition to complete
        $workflowService = app(WorkflowService::class);
        $workflowService->executeTransition($movement, $transition->id, $admin, 'Goods received');

        // Check stock in Central Depot is 50, and Unit Workshop is 0
        $this->assertEquals(50, $item->fresh()->current_stock);
        $this->assertEquals(50, $item->stockInWarehouse($mainWH->id));
        $this->assertEquals(0, $item->stockInWarehouse($unitWH->id));

        // 6. Test Subject Clerk (Scoped to Unit WH) viewing stock balance
        $this->actingAs($clerk);
        $clerkBalanceRes = $this->get(route('stock.balance'));
        $clerkBalanceRes->assertStatus(200);
        $clerkBalanceRes->assertSee('Workshop Unit #1');
        // Total units for Unit WH is 0
        $clerkBalanceRes->assertSee('Out of Stock');

        // 7. Execute Inbound to Unit WH (Qty 10)
        $unitMovement = \App\Models\StockMovement::create([
            'organization_id' => $org->id,
            'warehouse_id' => $unitWH->id,
            'type' => 'inbound',
            'reference_code' => 'REQ-UNIT-01',
            'created_by' => $clerk->id,
            'current_state' => 'draft',
            'total_quantity' => 10,
        ]);
        $unitMovement->items()->create([
            'organization_id' => $org->id,
            'inventory_item_id' => $item->id,
            'quantity' => 10,
            'item_lot_number' => 'LOT-UNIT-01',
        ]);

        $workflowService->executeTransition($unitMovement, $transition->id, $admin, 'Unit received');

        $this->assertEquals(60, $item->fresh()->current_stock); // 50 in main + 10 in unit
        $this->assertEquals(50, $item->stockInWarehouse($mainWH->id));
        $this->assertEquals(10, $item->stockInWarehouse($unitWH->id));

        // 8. Test Admin updating warehouse details via PUT route
        $this->actingAs($admin);
        $updateWHRes = $this->put(route('inventory.warehouses.update', $unitWH->id), [
            'name' => 'Workshop Unit #1 (Heavy Repair)',
            'type' => 'unit',
            'location' => 'Field Garage Sector 4',
        ]);
        $updateWHRes->assertRedirect(route('inventory.warehouses'));
        $this->assertEquals('Workshop Unit #1 (Heavy Repair)', $unitWH->fresh()->name);
    }
}
