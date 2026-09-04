<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\Organization;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WorkflowDefinition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardRedesignTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_executive_kpis_and_analytics(): void
    {
        $org = Organization::create(['name' => 'Apex Logistics Corp', 'code' => 'APEX', 'status' => 'active']);
        $user = User::create([
            'name' => 'John Admin',
            'email' => 'admin@apexlogistics.com',
            'password' => bcrypt('password'),
            'organization_id' => $org->id,
            'is_org_admin' => true,
            'is_super_admin' => false,
        ]);

        $cat = Category::create([
            'organization_id' => $org->id,
            'level' => 1,
            'name' => 'Electronics & Hardware',
            'code' => 'ELEC',
        ]);

        $wh = Warehouse::create([
            'organization_id' => $org->id,
            'name' => 'Main Depot',
        ]);

        $item1 = InventoryItem::create([
            'organization_id' => $org->id,
            'name' => 'Dell Latitude Laptop',
            'sku' => 'LAP-DELL-5420',
            'category_1_id' => $cat->id,
            'current_stock' => 25,
            'reorder_level' => 10,
            'unit' => 'units',
            'unit_cost' => 850.00,
        ]);

        $item2 = InventoryItem::create([
            'organization_id' => $org->id,
            'name' => 'Cat6 Ethernet Cable',
            'sku' => 'CAB-CAT6-100',
            'category_1_id' => $cat->id,
            'current_stock' => 3,
            'reorder_level' => 15,
            'unit' => 'rolls',
            'unit_cost' => 45.00,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Welcome back, ' . $user->name);
        $response->assertSee('Apex Logistics Corp');
        $response->assertSee('Total Items');
        $response->assertSee('Total Valuation');
        $response->assertSee('Stock Amounts');
        $response->assertSee('Stock Health');
        $response->assertSee('Low Stock Items Reorder Alert');
        $response->assertSee('Recent Stock Movements');
        $response->assertSee('LAP-DELL-5420');
        $response->assertSee('CAB-CAT6-100');
    }
}
