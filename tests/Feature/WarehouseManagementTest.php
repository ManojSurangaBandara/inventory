<?php

namespace Tests\Feature;

use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WarehouseManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    #[Test]
    public function tenant_admin_can_delete_mistakenly_created_warehouse_with_zero_dependencies()
    {
        $admin = User::where('email', 'admin@apexlogistics.com')->first();
        $this->actingAs($admin);

        $whType = \App\Models\WarehouseType::where('organization_id', $admin->organization_id)->first()
            ?? \App\Models\WarehouseType::ensureDefaults($admin->organization_id)->first();

        // Create a new warehouse
        $storeRes = $this->post(route('inventory.warehouses.store'), [
            'name' => 'Mistaken Depot East',
            'warehouse_type_id' => $whType->id,
            'location' => 'Block 9, East Yard',
        ]);
        $storeRes->assertRedirect(route('inventory.warehouses'));

        $warehouse = Warehouse::where('name', 'Mistaken Depot East')->first();
        $this->assertNotNull($warehouse);

        // Delete the unused warehouse -> succeeds
        $deleteRes = $this->delete(route('inventory.warehouses.destroy', $warehouse->id));
        $deleteRes->assertRedirect(route('inventory.warehouses'));
        $deleteRes->assertSessionHas('success');

        $this->assertDatabaseMissing('warehouses', ['id' => $warehouse->id]);
    }

    #[Test]
    public function cannot_delete_warehouse_when_stock_movements_or_purchase_orders_exist()
    {
        $admin = User::where('email', 'admin@apexlogistics.com')->first();
        $this->actingAs($admin);

        // Find a seeded warehouse that is referenced in stock movements
        $usedWarehouse = Warehouse::first();
        $this->assertNotNull($usedWarehouse);

        // Ensure there is at least one transaction referencing this warehouse
        $this->assertTrue(
            StockMovement::where('warehouse_id', $usedWarehouse->id)->exists() ||
            PurchaseOrder::where('warehouse_id', $usedWarehouse->id)->exists()
        );

        // Attempt deletion -> blocked by dependency check
        $res = $this->delete(route('inventory.warehouses.destroy', $usedWarehouse->id));
        $res->assertRedirect(route('inventory.warehouses'));
        $res->assertSessionHas('error');

        // Verify warehouse still exists in database
        $this->assertDatabaseHas('warehouses', ['id' => $usedWarehouse->id]);
    }

    #[Test]
    public function delete_button_is_only_visible_for_warehouses_with_zero_dependencies()
    {
        $admin = User::where('email', 'admin@apexlogistics.com')->first();
        $this->actingAs($admin);

        // Create an unused warehouse
        $unused = Warehouse::create([
            'organization_id' => $admin->organization_id,
            'name' => 'Zero Dependency Facility',
            'location' => 'Sector 4',
        ]);

        $used = Warehouse::where('name', '!=', 'Zero Dependency Facility')->first();

        $res = $this->get(route('inventory.warehouses'));
        $res->assertOk();

        // The unused warehouse has delete form
        $res->assertSee(route('inventory.warehouses.destroy', $unused->id));

        // The used warehouse does NOT have delete form
        $res->assertDontSee(route('inventory.warehouses.destroy', $used->id));
    }
}
