<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\Organization;
use App\Models\StockMovement;
use App\Models\StockMovementItem;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemModificationProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_unused_item_can_be_edited_and_deleted(): void
    {
        $org = Organization::create(['name' => 'Apex Logistics', 'code' => 'APEX', 'status' => 'active']);
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@apexlogistics.com',
            'password' => bcrypt('password'),
            'organization_id' => $org->id,
            'is_org_admin' => true,
            'is_super_admin' => false,
        ]);

        $cat = Category::create([
            'organization_id' => $org->id,
            'level' => 1,
            'name' => 'General Hardware',
            'code' => 'GEN',
        ]);

        $item = InventoryItem::create([
            'organization_id' => $org->id,
            'name' => 'Mistakenly Created Drill',
            'sku' => 'DRL-MISTAKE-01',
            'category_id' => $cat->id,
            'unit' => 'pcs',
            'unit_cost' => 100.00,
            'reorder_level' => 5,
            'current_stock' => 0,
        ]);

        $this->actingAs($admin);

        // Verify Edit and Delete buttons are rendered for unused items
        $pageRes = $this->get(route('inventory.items'));
        $pageRes->assertStatus(200);
        $pageRes->assertSee('Edit');
        $pageRes->assertSee('Delete');
        $pageRes->assertDontSee('In Use');

        // Edit the unused item
        $editRes = $this->put(route('inventory.items.update', $item->id), [
            'name' => 'Corrected Drill Name',
            'sku' => 'DRL-CORRECTED-01',
            'category_id' => $cat->id,
            'unit' => 'pcs',
            'unit_cost' => 120.00,
            'reorder_level' => 10,
        ]);
        $editRes->assertRedirect(route('inventory.items'));
        $this->assertEquals('Corrected Drill Name', $item->fresh()->name);

        // Delete the unused item
        $delRes = $this->delete(route('inventory.items.destroy', $item->id));
        $delRes->assertRedirect(route('inventory.items'));
        $this->assertDatabaseMissing('inventory_items', ['id' => $item->id]);
    }

    public function test_used_item_cannot_be_edited_or_deleted(): void
    {
        $org = Organization::create(['name' => 'Apex Logistics', 'code' => 'APEX', 'status' => 'active']);
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@apexlogistics.com',
            'password' => bcrypt('password'),
            'organization_id' => $org->id,
            'is_org_admin' => true,
            'is_super_admin' => false,
        ]);

        $cat = Category::create([
            'organization_id' => $org->id,
            'level' => 1,
            'name' => 'General Hardware',
            'code' => 'GEN',
        ]);

        $wh = Warehouse::create([
            'organization_id' => $org->id,
            'name' => 'Main Warehouse',
        ]);

        $usedItem = InventoryItem::create([
            'organization_id' => $org->id,
            'name' => 'In-Use Production Cable',
            'sku' => 'CAB-PROD-999',
            'category_id' => $cat->id,
            'unit' => 'meters',
            'unit_cost' => 15.00,
            'reorder_level' => 50,
            'current_stock' => 100, // Stock on hand > 0
        ]);

        // Add a stock movement line item
        $movement = StockMovement::create([
            'organization_id' => $org->id,
            'reference_code' => 'REQ-TEST-001',
            'type' => 'inbound',
            'warehouse_id' => $wh->id,
            'current_state' => 'approved',
            'created_by' => $admin->id,
        ]);

        StockMovementItem::create([
            'organization_id' => $org->id,
            'stock_movement_id' => $movement->id,
            'inventory_item_id' => $usedItem->id,
            'quantity' => 100,
        ]);

        $this->actingAs($admin);

        // Verify In Use badge is rendered instead of Edit/Delete
        $pageRes = $this->get(route('inventory.items'));
        $pageRes->assertStatus(200);
        $pageRes->assertSee('In Use');

        // Attempting to update a used item is rejected
        $editRes = $this->put(route('inventory.items.update', $usedItem->id), [
            'name' => 'Tampered Name',
            'sku' => 'CAB-PROD-999',
            'category_id' => $cat->id,
            'unit' => 'meters',
            'unit_cost' => 20.00,
            'reorder_level' => 50,
        ]);
        $editRes->assertSessionHas('error');
        $this->assertEquals('In-Use Production Cable', $usedItem->fresh()->name);

        // Attempting to delete a used item is rejected
        $delRes = $this->delete(route('inventory.items.destroy', $usedItem->id));
        $delRes->assertSessionHas('error');
        $this->assertDatabaseHas('inventory_items', ['id' => $usedItem->id]);
    }
}
