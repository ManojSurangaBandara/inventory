<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockBalanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_access_stock_balance_page_and_view_kpis(): void
    {
        $org = Organization::create(['name' => 'Apex Logistics', 'code' => 'APEX', 'status' => 'active']);
        $user = User::create([
            'name' => 'Staff Member',
            'email' => 'staff@apexlogistics.com',
            'password' => bcrypt('password'),
            'organization_id' => $org->id,
            'is_org_admin' => false,
        ]);

        $cat = Category::create([
            'organization_id' => $org->id,
            'level' => 1,
            'name' => 'Heavy Machinery',
            'code' => 'MACH',
        ]);

        InventoryItem::create([
            'organization_id' => $org->id,
            'category_id' => $cat->id,
            'sku' => 'ITM-001',
            'name' => 'Hydraulic Press',
            'unit' => 'pcs',
            'unit_cost' => 5000.00,
            'reorder_level' => 5,
            'current_stock' => 20,
        ]);

        InventoryItem::create([
            'organization_id' => $org->id,
            'category_id' => $cat->id,
            'sku' => 'ITM-002',
            'name' => 'Pneumatic Hose',
            'unit' => 'meters',
            'unit_cost' => 150.00,
            'reorder_level' => 10,
            'current_stock' => 4, // Low stock
        ]);

        InventoryItem::create([
            'organization_id' => $org->id,
            'category_id' => $cat->id,
            'sku' => 'ITM-003',
            'name' => 'Spare Valve',
            'unit' => 'pcs',
            'unit_cost' => 80.00,
            'reorder_level' => 15,
            'current_stock' => 0, // Out of stock
        ]);

        $this->actingAs($user);

        $res = $this->get(route('stock.balance'));
        $res->assertStatus(200);
        $res->assertSee('Current Stock Balance');
        $res->assertSee('Hydraulic Press');
        $res->assertSee('Pneumatic Hose');
        $res->assertSee('Spare Valve');
        $res->assertSee('Adequate');
        $res->assertSee('Low Stock');
        $res->assertSee('Out of Stock');
        $res->assertSee('Rs.');

        // Test filtering by stock_status=low_stock
        $lowRes = $this->get(route('stock.balance', ['stock_status' => 'low_stock']));
        $lowRes->assertStatus(200);
        $lowRes->assertSee('Pneumatic Hose');
        $lowRes->assertDontSee('Hydraulic Press');
    }
}
