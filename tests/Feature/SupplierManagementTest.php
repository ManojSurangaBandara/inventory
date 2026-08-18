<?php

namespace Tests\Feature;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SupplierManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    #[Test]
    public function tenant_admin_can_delete_mistakenly_created_supplier_with_zero_dependencies()
    {
        $admin = User::where('email', 'admin@apexlogistics.com')->first();
        $this->actingAs($admin);

        // Create a new supplier
        $storeRes = $this->post(route('inventory.suppliers.store'), [
            'name' => 'Mistaken Supplier Corp',
            'email' => 'contact@mistakensupplier.com',
            'phone' => '+1 555 9999',
            'address' => '123 Fake Street',
        ]);
        $storeRes->assertRedirect(route('inventory.suppliers'));

        $supplier = Supplier::where('name', 'Mistaken Supplier Corp')->first();
        $this->assertNotNull($supplier);

        // Delete the unused supplier -> succeeds
        $deleteRes = $this->delete(route('inventory.suppliers.destroy', $supplier->id));
        $deleteRes->assertRedirect(route('inventory.suppliers'));
        $deleteRes->assertSessionHas('success');

        $this->assertDatabaseMissing('suppliers', ['id' => $supplier->id]);
    }

    #[Test]
    public function cannot_delete_supplier_when_purchase_orders_exist()
    {
        $admin = User::where('email', 'admin@apexlogistics.com')->first();
        $this->actingAs($admin);

        // Find a seeded supplier that is referenced in purchase orders
        $usedSupplier = Supplier::whereHas('purchaseOrders')->first();
        if (!$usedSupplier) {
            $usedSupplier = Supplier::first();
            PurchaseOrder::create([
                'organization_id' => $admin->organization_id,
                'supplier_id' => $usedSupplier->id,
                'warehouse_id' => 1,
                'po_number' => 'PO-TEST-DEP-001',
                'status' => 'draft',
                'order_date' => now(),
                'total_amount' => 500,
            ]);
        }

        $this->assertTrue($usedSupplier->purchaseOrders()->exists());

        // Attempt deletion -> blocked by dependency check
        $res = $this->delete(route('inventory.suppliers.destroy', $usedSupplier->id));
        $res->assertRedirect(route('inventory.suppliers'));
        $res->assertSessionHas('error');

        // Verify supplier still exists in database
        $this->assertDatabaseHas('suppliers', ['id' => $usedSupplier->id]);
    }

    #[Test]
    public function delete_button_is_only_visible_for_suppliers_with_zero_dependencies()
    {
        $admin = User::where('email', 'admin@apexlogistics.com')->first();
        $this->actingAs($admin);

        // Create an unused supplier
        $unused = Supplier::create([
            'organization_id' => $admin->organization_id,
            'name' => 'Zero Dependency Vendor',
            'email' => 'zero@vendor.com',
        ]);

        $used = Supplier::whereHas('purchaseOrders')->first();
        if (!$used) {
            $used = Supplier::where('id', '!=', $unused->id)->first();
            PurchaseOrder::create([
                'organization_id' => $admin->organization_id,
                'supplier_id' => $used->id,
                'warehouse_id' => 1,
                'po_number' => 'PO-TEST-VIS-002',
                'status' => 'draft',
                'order_date' => now(),
                'total_amount' => 750,
            ]);
        }

        $res = $this->get(route('inventory.suppliers'));
        $res->assertOk();

        // The unused supplier has delete form
        $res->assertSee(route('inventory.suppliers.destroy', $unused->id));

        // The used supplier does NOT have delete form
        $res->assertDontSee(route('inventory.suppliers.destroy', $used->id));
    }

    #[Test]
    public function regular_organization_staff_cannot_delete_suppliers()
    {
        $clerk = User::where('email', 'clerk@apexlogistics.com')->first();
        $supplier = Supplier::first();

        // Clerk tries to delete supplier -> 403 Forbidden
        $res = $this->actingAs($clerk)->delete(route('inventory.suppliers.destroy', $supplier->id));
        $res->assertStatus(403);

        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id]);
    }
}
