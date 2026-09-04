<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WarehouseHierarchyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    #[Test]
    public function can_create_warehouse_with_parent_warehouse_relationship()
    {
        $admin = User::where('email', 'admin@apexlogistics.com')->first();
        $this->actingAs($admin);

        $mainType = WarehouseType::where('organization_id', $admin->organization_id)->where('code', 'MAIN')->first()
            ?? WarehouseType::create(['organization_id' => $admin->organization_id, 'name' => 'Main Warehouse', 'code' => 'MAIN']);

        $subType = WarehouseType::where('organization_id', $admin->organization_id)->where('code', 'SUB')->first()
            ?? WarehouseType::create(['organization_id' => $admin->organization_id, 'name' => 'Sub Warehouse', 'code' => 'SUB']);

        // 1. Create Central Depot (Top-Level)
        $parentRes = $this->post(route('inventory.warehouses.store'), [
            'name' => 'Apex Central Hub',
            'code' => 'WH-APEX-CENTRAL',
            'warehouse_type_id' => $mainType->id,
            'location' => 'Capital City Logistics Park',
            'parent_warehouse_id' => null,
        ]);
        $parentRes->assertRedirect(route('inventory.warehouses'));

        $centralWh = Warehouse::where('code', 'WH-APEX-CENTRAL')->first();
        $this->assertNotNull($centralWh);
        $this->assertNull($centralWh->parent_warehouse_id);

        // 2. Create Sub-Depot reporting to Central Depot
        $childRes = $this->post(route('inventory.warehouses.store'), [
            'name' => 'Apex Regional Sub-Hub',
            'code' => 'WH-APEX-SUB1',
            'warehouse_type_id' => $subType->id,
            'location' => 'Northern District',
            'parent_warehouse_id' => $centralWh->id,
        ]);
        $childRes->assertRedirect(route('inventory.warehouses'));

        $subWh = Warehouse::where('code', 'WH-APEX-SUB1')->first();
        $this->assertNotNull($subWh);
        $this->assertEquals($centralWh->id, $subWh->parent_warehouse_id);
        $this->assertEquals($centralWh->id, $subWh->parent->id);
        $this->assertTrue($centralWh->children->contains($subWh));
    }

    #[Test]
    public function warehouse_page_displays_parent_and_child_hierarchy_badges()
    {
        $admin = User::where('email', 'admin@apexlogistics.com')->first();
        $this->actingAs($admin);

        $parent = Warehouse::create([
            'organization_id' => $admin->organization_id,
            'name' => 'Metropolitan Supply Depot',
            'code' => 'WH-METRO',
            'location' => 'Metro Center',
        ]);

        $child = Warehouse::create([
            'organization_id' => $admin->organization_id,
            'name' => 'North Station Depot',
            'code' => 'WH-NORTH',
            'location' => 'North Gate',
            'parent_warehouse_id' => $parent->id,
        ]);

        $res = $this->get(route('inventory.warehouses'));
        $res->assertOk();
        $res->assertSee('Metropolitan Supply Depot');
        $res->assertSee('North Station Depot');
        $res->assertSee('Reports to:');
        $res->assertSee('Parent Hub:');
        // Hierarchy Tree View assertions
        $res->assertSee('Hierarchy Tree');
        $res->assertSee('Facility Network Topology');
        $res->assertSee('Tier 1: Primary Central Hub');
        $res->assertSee('Tier 2: Regional Sub-Depot');
        $res->assertSee('Add Sub-Facility');
    }

    #[Test]
    public function prevents_setting_warehouse_as_its_own_parent()
    {
        $admin = User::where('email', 'admin@apexlogistics.com')->first();
        $this->actingAs($admin);

        $wh = Warehouse::create([
            'organization_id' => $admin->organization_id,
            'name' => 'Solo Depot',
            'code' => 'WH-SOLO',
            'location' => 'Harbor Road',
        ]);

        // Attempt to update self as own parent
        $res = $this->put(route('inventory.warehouses.update', $wh->id), [
            'name' => 'Solo Depot Updated',
            'code' => 'WH-SOLO',
            'parent_warehouse_id' => $wh->id,
        ]);

        $res->assertSessionHas('error');
        $this->assertNull($wh->fresh()->parent_warehouse_id);
    }

    #[Test]
    public function prevents_circular_hierarchy_descendant_cannot_be_parent()
    {
        $admin = User::where('email', 'admin@apexlogistics.com')->first();
        $this->actingAs($admin);

        // Level 1 -> Level 2 -> Level 3
        $hub = Warehouse::create([
            'organization_id' => $admin->organization_id,
            'name' => 'Tier 1 Hub',
            'code' => 'WH-T1',
        ]);

        $sub = Warehouse::create([
            'organization_id' => $admin->organization_id,
            'name' => 'Tier 2 Sub',
            'code' => 'WH-T2',
            'parent_warehouse_id' => $hub->id,
        ]);

        $unit = Warehouse::create([
            'organization_id' => $admin->organization_id,
            'name' => 'Tier 3 Unit',
            'code' => 'WH-T3',
            'parent_warehouse_id' => $sub->id,
        ]);

        $this->assertEquals([$sub->id, $unit->id], $hub->allDescendantIds());

        // Attempt to set Tier 3 as parent of Tier 1 Hub (would form a cycle!)
        $res = $this->put(route('inventory.warehouses.update', $hub->id), [
            'name' => 'Tier 1 Hub',
            'code' => 'WH-T1',
            'parent_warehouse_id' => $unit->id,
        ]);

        $res->assertSessionHas('error');
        $this->assertNull($hub->fresh()->parent_warehouse_id);
    }

    #[Test]
    public function cannot_delete_parent_warehouse_when_children_exist()
    {
        $admin = User::where('email', 'admin@apexlogistics.com')->first();
        $this->actingAs($admin);

        $parentWh = Warehouse::create([
            'organization_id' => $admin->organization_id,
            'name' => 'Parent Hub Unused',
            'code' => 'WH-PARENT-TEST',
        ]);

        $childWh = Warehouse::create([
            'organization_id' => $admin->organization_id,
            'name' => 'Child Depot Unused',
            'code' => 'WH-CHILD-TEST',
            'parent_warehouse_id' => $parentWh->id,
        ]);

        // Attempt deleting parent -> blocked by child count check
        $res = $this->delete(route('inventory.warehouses.destroy', $parentWh->id));
        $res->assertRedirect(route('inventory.warehouses'));
        $res->assertSessionHas('error');
        $this->assertDatabaseHas('warehouses', ['id' => $parentWh->id]);

        // Delete child first -> succeeds
        $childDelete = $this->delete(route('inventory.warehouses.destroy', $childWh->id));
        $childDelete->assertSessionHas('success');
        $this->assertDatabaseMissing('warehouses', ['id' => $childWh->id]);

        // Now parent can be deleted
        $parentDelete = $this->delete(route('inventory.warehouses.destroy', $parentWh->id));
        $parentDelete->assertSessionHas('success');
        $this->assertDatabaseMissing('warehouses', ['id' => $parentWh->id]);
    }

    #[Test]
    public function tenant_cannot_assign_parent_from_another_organization()
    {
        $admin = User::where('email', 'admin@apexlogistics.com')->first();
        $this->actingAs($admin);

        $otherOrg = Organization::create([
            'name' => 'Foreign Corp',
            'slug' => 'foreign-corp',
            'code' => 'FOR',
        ]);

        $foreignWh = Warehouse::create([
            'organization_id' => $otherOrg->id,
            'name' => 'Foreign Depot',
            'code' => 'WH-FOREIGN',
        ]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->withoutExceptionHandling()->post(route('inventory.warehouses.store'), [
            'name' => 'Illegal Sub Depot',
            'code' => 'WH-ILLEGAL',
            'parent_warehouse_id' => $foreignWh->id,
        ]);
    }
}
