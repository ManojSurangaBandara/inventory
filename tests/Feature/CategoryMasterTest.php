<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CategoryMasterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    #[Test]
    public function can_create_four_levels_of_categories_and_register_item_master()
    {
        $admin = User::where('email', 'admin@apexlogistics.com')->first();
        $this->actingAs($admin);

        // Step 1: Create Category 1 (Required level)
        $res1 = $this->post(route('inventory.categories.store'), [
            'level' => 1,
            'name' => 'Machinery & Tools',
            'description' => 'Heavy manufacturing tools',
        ]);
        $res1->assertRedirect();
        $cat1 = Category::where('name', 'Machinery & Tools')->first();
        $this->assertNotNull($cat1);
        $this->assertEquals(1, $cat1->level);

        // Step 2: Create Category 2 (Optional level, linked to Cat 1)
        $res2 = $this->post(route('inventory.categories.store'), [
            'level' => 2,
            'parent_id' => $cat1->id,
            'name' => 'Drilling Equipment',
        ]);
        $res2->assertRedirect();
        $cat2 = Category::where('name', 'Drilling Equipment')->first();
        $this->assertNotNull($cat2);
        $this->assertEquals(2, $cat2->level);
        $this->assertEquals($cat1->id, $cat2->parent_id);

        // Step 3: Create Category 3 (Optional level, linked to Cat 2)
        $res3 = $this->post(route('inventory.categories.store'), [
            'level' => 3,
            'parent_id' => $cat2->id,
            'name' => 'Pneumatic Drills',
        ]);
        $res3->assertRedirect();
        $cat3 = Category::where('name', 'Pneumatic Drills')->first();
        $this->assertNotNull($cat3);
        $this->assertEquals(3, $cat3->level);

        // Step 4: Create Category 4 (Optional level, linked to Cat 3)
        $res4 = $this->post(route('inventory.categories.store'), [
            'level' => 4,
            'parent_id' => $cat3->id,
            'name' => 'Cordless Heavy-Duty Drills',
        ]);
        $res4->assertRedirect();
        $cat4 = Category::where('name', 'Cordless Heavy-Duty Drills')->first();
        $this->assertNotNull($cat4);
        $this->assertEquals(4, $cat4->level);
        $this->assertEquals('Machinery & Tools > Drilling Equipment > Pneumatic Drills > Cordless Heavy-Duty Drills', $cat4->full_path);

        // Step 5: Register Item in Master Data (opening stock is always 0, no stock input allowed)
        $itemRes = $this->post(route('inventory.items.store'), [
            'sku' => 'DRL-PNU-2000',
            'name' => 'Industrial Pneumatic Drill 2000W',
            'category_id' => $cat1->id,
            'category_2_id' => $cat2->id,
            'category_3_id' => $cat3->id,
            'category_4_id' => $cat4->id,
            'unit' => 'pcs',
            'unit_cost' => 450.00,
            'reorder_level' => 5,
            'description' => 'High torque pneumatic drill',
        ]);
        $itemRes->assertRedirect(route('inventory.items'));

        $item = InventoryItem::where('sku', 'DRL-PNU-2000')->first();
        $this->assertNotNull($item);
        $this->assertEquals(0, $item->current_stock); // Opening stock must be 0
        $this->assertEquals($cat1->id, $item->category_id);
        $this->assertEquals($cat2->id, $item->category_2_id);
        $this->assertEquals($cat3->id, $item->category_3_id);
        $this->assertEquals($cat4->id, $item->category_4_id);
        $this->assertEquals('Machinery & Tools > Drilling Equipment > Pneumatic Drills > Cordless Heavy-Duty Drills', $item->category_trail);
    }

    #[Test]
    public function regular_organization_staff_cannot_access_master_data()
    {
        $clerk = User::where('email', 'clerk@apexlogistics.com')->first();
        $oc = User::where('email', 'oc@apexlogistics.com')->first();

        // Clerk tries to access Item Master Catalog -> 403 Forbidden
        $this->actingAs($clerk)->get(route('inventory.items'))->assertStatus(403);
        $this->actingAs($clerk)->get(route('inventory.categories'))->assertStatus(403);
        $this->actingAs($clerk)->get(route('inventory.suppliers'))->assertStatus(403);
        $this->actingAs($clerk)->get(route('inventory.warehouses'))->assertStatus(403);

        // OC tries to access Category Master -> 403 Forbidden
        $this->actingAs($oc)->get(route('inventory.categories'))->assertStatus(403);
    }
}
