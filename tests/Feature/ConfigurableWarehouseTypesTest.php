<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConfigurableWarehouseTypesTest extends TestCase
{
    use RefreshDatabase;

    public function test_configurable_warehouse_types_master_data_lifecycle_and_warehouse_linking(): void
    {
        $org = Organization::create(['name' => 'Apex Logistics', 'code' => 'APEX', 'status' => 'active']);

        $admin = User::create([
            'name' => 'Org Admin',
            'email' => 'admin@apexlogistics.com',
            'password' => bcrypt('password'),
            'organization_id' => $org->id,
            'is_org_admin' => true,
        ]);

        $this->actingAs($admin);

        // 1. Visit warehouse types master data page - triggers ensureDefaults
        $res = $this->get(route('inventory.warehouse-types'));
        $res->assertStatus(200);
        $res->assertSee('Warehouse Types');

        // Check defaults were seeded
        $types = WarehouseType::where('organization_id', $org->id)->get();
        $this->assertCount(3, $types);
        $this->assertTrue($types->contains('code', 'MAIN'));
        $this->assertTrue($types->contains('code', 'SUB'));
        $this->assertTrue($types->contains('code', 'UNIT'));

        // 2. Create custom warehouse type taking ONLY name
        $createTypeRes = $this->post(route('inventory.warehouse-types.store'), [
            'name' => 'Cold Storage Facility',
        ]);
        $createTypeRes->assertSessionHas('success');

        $coldType = WarehouseType::where('organization_id', $org->id)->where('name', 'Cold Storage Facility')->first();
        $this->assertNotNull($coldType);
        $this->assertEquals('COLD_STORAGE_FACILITY', $coldType->code);

        // 3. Create a warehouse assigned to the custom warehouse type (warehouse_type_id required)
        $createWHRes = $this->post(route('inventory.warehouses.store'), [
            'name' => 'Colombo Cold Hub #1',
            'code' => 'WH-COLD-01',
            'warehouse_type_id' => $coldType->id,
            'location' => 'Port Zone 3, Colombo',
        ]);
        $createWHRes->assertRedirect(route('inventory.warehouses'));

        $warehouse = Warehouse::where('organization_id', $org->id)->where('code', 'WH-COLD-01')->first();
        $this->assertNotNull($warehouse);
        $this->assertEquals($coldType->id, $warehouse->warehouse_type_id);
        $this->assertEquals('Cold Storage Facility', $warehouse->type_label);

        // 4. Update the warehouse type taking ONLY name
        $updateTypeRes = $this->put(route('inventory.warehouse-types.update', $coldType->id), [
            'name' => 'Ultra-Low Temperature Depot',
        ]);
        $updateTypeRes->assertSessionHas('success');

        $coldType->refresh();
        $this->assertEquals('Ultra-Low Temperature Depot', $coldType->name);
        $this->assertEquals('ULTRA_LOW_TEMPERATURE_DEPOT', $coldType->code);

        $warehouse->refresh();
        $this->assertEquals('Ultra-Low Temperature Depot', $warehouse->type_label);

        // 5. Attempting to delete a warehouse type with linked warehouses should be blocked
        $deleteBlockedRes = $this->delete(route('inventory.warehouse-types.destroy', $coldType->id));
        $deleteBlockedRes->assertSessionHas('error');
        $this->assertDatabaseHas('warehouse_types', ['id' => $coldType->id]);

        // 6. Delete an unused warehouse type -> succeeds
        $unusedType = WarehouseType::create([
            'organization_id' => $org->id,
            'name' => 'Temporary Transit Staging',
            'code' => 'TRANSIT',
        ]);
        $deleteRes = $this->delete(route('inventory.warehouse-types.destroy', $unusedType->id));
        $deleteRes->assertSessionHas('success');
        $this->assertDatabaseMissing('warehouse_types', ['id' => $unusedType->id]);
    }
}
