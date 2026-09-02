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

    public function test_configurable_warehouse_types_lifecycle_and_warehouse_linking(): void
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

        // 1. Visit warehouses page - triggers ensureDefaults
        $res = $this->get(route('inventory.warehouses'));
        $res->assertStatus(200);

        // Check defaults were seeded
        $types = WarehouseType::where('organization_id', $org->id)->get();
        $this->assertCount(3, $types);
        $this->assertTrue($types->contains('code', 'MAIN'));
        $this->assertTrue($types->contains('code', 'SUB'));
        $this->assertTrue($types->contains('code', 'UNIT'));

        // 2. Create custom warehouse type (Cold Storage Depot)
        $createTypeRes = $this->post(route('inventory.warehouse-types.store'), [
            'name' => 'Cold Storage Facility',
            'code' => 'COLD',
            'color' => 'cyan',
            'description' => 'Refrigerated vaccine and perishables depot',
        ]);
        $createTypeRes->assertRedirect(route('inventory.warehouses'));

        $coldType = WarehouseType::where('organization_id', $org->id)->where('code', 'COLD')->first();
        $this->assertNotNull($coldType);
        $this->assertEquals('Cold Storage Facility', $coldType->name);
        $this->assertStringContainsString('cyan', $coldType->badge_class);

        // 3. Create a warehouse assigned to the custom warehouse type
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
        $this->assertEquals($coldType->badge_class, $warehouse->type_badge_class);

        // 4. Update the warehouse type name and color
        $updateTypeRes = $this->put(route('inventory.warehouse-types.update', $coldType->id), [
            'name' => 'Ultra-Low Temperature Depot',
            'code' => 'COLD-ULT',
            'color' => 'indigo',
            'description' => 'Deep freeze storage',
        ]);
        $updateTypeRes->assertRedirect(route('inventory.warehouses'));

        $warehouse->refresh();
        $this->assertEquals('Ultra-Low Temperature Depot', $warehouse->type_label);
        $this->assertStringContainsString('indigo', $warehouse->type_badge_class);

        // 5. Attempting to delete a warehouse type with linked warehouses should be blocked
        $deleteBlockedRes = $this->delete(route('inventory.warehouse-types.destroy', $coldType->id));
        $deleteBlockedRes->assertRedirect(route('inventory.warehouses'));
        $this->assertDatabaseHas('warehouse_types', ['id' => $coldType->id]);

        // 6. Assign user to this warehouse with custom type
        $clerk = User::create([
            'name' => 'Cold Facility Clerk',
            'email' => 'coldclerk@apexlogistics.com',
            'password' => bcrypt('password'),
            'organization_id' => $org->id,
            'warehouse_id' => $warehouse->id,
            'is_org_admin' => false,
        ]);

        $this->assertEquals($warehouse->id, $clerk->warehouse_id);
        $this->assertEquals('Ultra-Low Temperature Depot', $clerk->warehouse->type_label);

        // 7. Delete an unlinked type (e.g. create a temporary type and delete it)
        $tempType = WarehouseType::create([
            'organization_id' => $org->id,
            'name' => 'Temporary Storage Yard',
            'code' => 'TEMP',
            'color' => 'rose',
        ]);

        $deleteTempRes = $this->delete(route('inventory.warehouse-types.destroy', $tempType->id));
        $deleteTempRes->assertRedirect(route('inventory.warehouses'));
        $this->assertDatabaseMissing('warehouse_types', ['id' => $tempType->id]);
    }
}
