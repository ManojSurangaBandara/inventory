<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Role;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowLog;
use App\Models\WorkflowState;
use App\Models\WorkflowTransition;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed System Permissions
        $permissions = [
            ['name' => 'View Inventory Items', 'slug' => 'items.view', 'module' => 'inventory'],
            ['name' => 'Create Inventory Items', 'slug' => 'items.create', 'module' => 'inventory'],
            ['name' => 'Edit Inventory Items', 'slug' => 'items.edit', 'module' => 'inventory'],
            ['name' => 'Delete Inventory Items', 'slug' => 'items.delete', 'module' => 'inventory'],

            ['name' => 'Initiate Stock Movements', 'slug' => 'stock.create', 'module' => 'stock'],
            ['name' => 'Approve Stock Release', 'slug' => 'stock.approve', 'module' => 'stock'],
            ['name' => 'Dispatch Stock', 'slug' => 'stock.dispatch', 'module' => 'stock'],

            ['name' => 'Create Purchase Orders', 'slug' => 'orders.create', 'module' => 'orders'],
            ['name' => 'Approve Purchase Orders', 'slug' => 'orders.approve', 'module' => 'orders'],

            ['name' => 'Manage Organization Users', 'slug' => 'users.manage', 'module' => 'users'],
            ['name' => 'Manage Roles & Permissions', 'slug' => 'roles.manage', 'module' => 'users'],
            ['name' => 'Manage UI Workflows', 'slug' => 'workflows.manage', 'module' => 'workflows'],
        ];

        $permModels = [];
        foreach ($permissions as $p) {
            $permModels[$p['slug']] = Permission::firstOrCreate(['slug' => $p['slug']], $p);
        }

        // 2. Seed Global Super Admin (No organization_id)
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@system.com'],
            [
                'name' => 'System Super Admin',
                'password' => Hash::make('password'),
                'is_super_admin' => true,
                'is_org_admin' => false,
                'organization_id' => null,
                'status' => 'active',
            ]
        );

        // 3. Seed Demo Tenant Organization 1: Apex Logistics
        $apexOrg = Organization::firstOrCreate(
            ['code' => 'apex'],
            [
                'name' => 'Apex Logistics',
                'email' => 'contact@apexlogistics.com',
                'phone' => '+1 (555) 019-2834',
                'address' => '100 Industrial Parkway, Sector 4',
                'status' => 'active',
            ]
        );

        // Apex Org Admin
        $apexAdmin = User::firstOrCreate(
            ['email' => 'admin@apexlogistics.com'],
            [
                'organization_id' => $apexOrg->id,
                'name' => 'Apex Admin',
                'password' => Hash::make('password'),
                'is_super_admin' => false,
                'is_org_admin' => true,
                'status' => 'active',
            ]
        );

        // Apex Custom Tenant Roles
        $managerRole = Role::firstOrCreate(
            ['organization_id' => $apexOrg->id, 'slug' => 'inventory-manager'],
            ['name' => 'Inventory Manager', 'description' => 'Oversees inventory stock and approves purchase orders', 'is_system' => false]
        );
        $managerRole->permissions()->sync(array_column($permModels, 'id'));

        $inspectorRole = Role::firstOrCreate(
            ['organization_id' => $apexOrg->id, 'slug' => 'quality-inspector'],
            ['name' => 'Quality Inspector', 'description' => 'Executes quality control inspection on incoming stock movements', 'is_system' => false]
        );
        $inspectorRole->permissions()->sync([
            $permModels['items.view']->id,
            $permModels['stock.create']->id,
            $permModels['stock.approve']->id,
        ]);

        $operatorRole = Role::firstOrCreate(
            ['organization_id' => $apexOrg->id, 'slug' => 'warehouse-operator'],
            ['name' => 'Warehouse Operator', 'description' => 'Performs physical warehouse receiving and movement entries', 'is_system' => false]
        );
        $operatorRole->permissions()->sync([
            $permModels['items.view']->id,
            $permModels['stock.create']->id,
        ]);

        // Apex Org User (Manager)
        $apexManagerUser = User::firstOrCreate(
            ['email' => 'manager@apexlogistics.com'],
            [
                'organization_id' => $apexOrg->id,
                'name' => 'David Manager',
                'password' => Hash::make('password'),
                'is_super_admin' => false,
                'is_org_admin' => false,
                'status' => 'active',
            ]
        );
        $apexManagerUser->roles()->sync([$managerRole->id]);

        // Master Data for Apex Logistics
        $catElectronics = Category::create(['organization_id' => $apexOrg->id, 'name' => 'Electronics & Computing', 'description' => 'Laptops, monitors, components']);
        $catParts = Category::create(['organization_id' => $apexOrg->id, 'name' => 'Industrial Parts', 'description' => 'Cables, sensors, robotics parts']);

        $whMain = Warehouse::create(['organization_id' => $apexOrg->id, 'name' => 'Main Distribution Hub', 'code' => 'WH-MAIN', 'location' => 'Zone A Depot']);
        $whQC = Warehouse::create(['organization_id' => $apexOrg->id, 'name' => 'Quality Inspection Vault', 'code' => 'WH-QC', 'location' => 'Zone B Hangar']);

        $supplierA = Supplier::create(['organization_id' => $apexOrg->id, 'name' => 'Apex Components Inc', 'email' => 'orders@apexcomp.com', 'phone' => '+1 800 555 1111']);
        $supplierB = Supplier::create(['organization_id' => $apexOrg->id, 'name' => 'Global Tech Distributors', 'email' => 'sales@globaltech.com', 'phone' => '+1 800 555 2222']);

        $itemLaptop = InventoryItem::create([
            'organization_id' => $apexOrg->id,
            'category_id' => $catElectronics->id,
            'sku' => 'LAP-XPS15',
            'name' => 'Dell XPS 15 Developer Edition',
            'description' => '32GB RAM, 1TB NVMe SSD, i9 Processor',
            'unit' => 'pcs',
            'unit_cost' => 1850.00,
            'reorder_level' => 10,
            'current_stock' => 45,
            'status' => 'active',
        ]);

        $itemMonitor = InventoryItem::create([
            'organization_id' => $apexOrg->id,
            'category_id' => $catElectronics->id,
            'sku' => 'MON-4K27',
            'name' => '27-inch UltraHD IPS Monitor',
            'description' => '4K USB-C Color-Calibrated Display',
            'unit' => 'pcs',
            'unit_cost' => 420.00,
            'reorder_level' => 15,
            'current_stock' => 28,
            'status' => 'active',
        ]);

        $itemCable = InventoryItem::create([
            'organization_id' => $apexOrg->id,
            'category_id' => $catParts->id,
            'sku' => 'CAB-FIBER10M',
            'name' => 'High-Speed Fiber Optic Cable 10m',
            'description' => 'Duplex Single Mode Optical Patch Cable',
            'unit' => 'pcs',
            'unit_cost' => 24.50,
            'reorder_level' => 25,
            'current_stock' => 4, // LOW STOCK TRIGGER!
            'status' => 'active',
        ]);

        // Pre-configured Stock Movement UI Workflow for Apex Logistics
        $smWorkflow = WorkflowDefinition::create([
            'organization_id' => $apexOrg->id,
            'name' => 'Stock Movement Quality Inspection Workflow',
            'entity_type' => 'StockMovement',
            'description' => 'Multi-step verification requiring QC inspection before stock release.',
            'is_active' => true,
        ]);

        $stDraft = WorkflowState::create(['workflow_definition_id' => $smWorkflow->id, 'code' => 'draft', 'name' => 'Draft Requisition', 'color' => 'slate', 'is_initial' => true, 'is_final' => false]);
        $stInspection = WorkflowState::create(['workflow_definition_id' => $smWorkflow->id, 'code' => 'inspection_pending', 'name' => 'QC Inspection Pending', 'color' => 'amber', 'is_initial' => false, 'is_final' => false]);
        $stApproved = WorkflowState::create(['workflow_definition_id' => $smWorkflow->id, 'code' => 'completed', 'name' => 'Passed & Released', 'color' => 'emerald', 'is_initial' => false, 'is_final' => true]);
        $stRejected = WorkflowState::create(['workflow_definition_id' => $smWorkflow->id, 'code' => 'rejected', 'name' => 'Rejected by Quality Control', 'color' => 'rose', 'is_initial' => false, 'is_final' => true]);

        WorkflowTransition::create([
            'workflow_definition_id' => $smWorkflow->id,
            'from_state_id' => $stDraft->id,
            'to_state_id' => $stInspection->id,
            'action_name' => 'Submit for QC Inspection',
            'allowed_roles' => ['warehouse-operator', 'inventory-manager'],
            'requires_note' => false,
        ]);

        WorkflowTransition::create([
            'workflow_definition_id' => $smWorkflow->id,
            'from_state_id' => $stInspection->id,
            'to_state_id' => $stApproved->id,
            'action_name' => 'Approve & Release Stock',
            'allowed_roles' => ['quality-inspector', 'inventory-manager'],
            'requires_note' => true,
        ]);

        WorkflowTransition::create([
            'workflow_definition_id' => $smWorkflow->id,
            'from_state_id' => $stInspection->id,
            'to_state_id' => $stRejected->id,
            'action_name' => 'Fail QC & Reject Stock',
            'allowed_roles' => ['quality-inspector'],
            'requires_note' => true,
        ]);

        // Sample Stock Movements
        $sm1 = StockMovement::create([
            'organization_id' => $apexOrg->id,
            'reference_code' => 'SM-INB001',
            'type' => 'inbound',
            'warehouse_id' => $whMain->id,
            'inventory_item_id' => $itemLaptop->id,
            'quantity' => 10,
            'current_state' => 'inspection_pending',
            'created_by' => $apexAdmin->id,
            'notes' => 'Shipment received from Dell.',
        ]);

        WorkflowLog::create([
            'organization_id' => $apexOrg->id,
            'entity_type' => 'StockMovement',
            'entity_id' => $sm1->id,
            'from_state' => 'draft',
            'to_state' => 'inspection_pending',
            'action' => 'Submit for QC Inspection',
            'user_id' => $apexAdmin->id,
            'notes' => 'Submitted to inspector team.',
        ]);

        // Pre-configured Purchase Order Workflow
        $poWorkflow = WorkflowDefinition::create([
            'organization_id' => $apexOrg->id,
            'name' => 'Purchase Order Approval Workflow',
            'entity_type' => 'PurchaseOrder',
            'description' => 'Multi-tier purchase order approval process',
            'is_active' => true,
        ]);

        $poDraft = WorkflowState::create(['workflow_definition_id' => $poWorkflow->id, 'code' => 'draft', 'name' => 'Draft PO', 'color' => 'slate', 'is_initial' => true, 'is_final' => false]);
        $poReview = WorkflowState::create(['workflow_definition_id' => $poWorkflow->id, 'code' => 'under_review', 'name' => 'Manager Review', 'color' => 'amber', 'is_initial' => false, 'is_final' => false]);
        $poOrdered = WorkflowState::create(['workflow_definition_id' => $poWorkflow->id, 'code' => 'ordered', 'name' => 'Supplier Ordered', 'color' => 'indigo', 'is_initial' => false, 'is_final' => false]);
        $poCompleted = WorkflowState::create(['workflow_definition_id' => $poWorkflow->id, 'code' => 'completed', 'name' => 'Completed & Received', 'color' => 'emerald', 'is_initial' => false, 'is_final' => true]);

        WorkflowTransition::create([
            'workflow_definition_id' => $poWorkflow->id,
            'from_state_id' => $poDraft->id,
            'to_state_id' => $poReview->id,
            'action_name' => 'Submit PO for Manager Approval',
            'allowed_roles' => [],
            'requires_note' => false,
        ]);

        WorkflowTransition::create([
            'workflow_definition_id' => $poWorkflow->id,
            'from_state_id' => $poReview->id,
            'to_state_id' => $poOrdered->id,
            'action_name' => 'Approve & Transmit PO to Supplier',
            'allowed_roles' => ['inventory-manager'],
            'requires_note' => true,
        ]);

        WorkflowTransition::create([
            'workflow_definition_id' => $poWorkflow->id,
            'from_state_id' => $poOrdered->id,
            'to_state_id' => $poCompleted->id,
            'action_name' => 'Mark Fully Received & Completed',
            'allowed_roles' => ['inventory-manager'],
            'requires_note' => false,
        ]);

        // Sample PO
        $po1 = PurchaseOrder::create([
            'organization_id' => $apexOrg->id,
            'po_number' => 'PO-20260811-0001',
            'supplier_id' => $supplierA->id,
            'warehouse_id' => $whMain->id,
            'total_amount' => 18500.00,
            'current_state' => 'under_review',
            'created_by' => $apexAdmin->id,
            'notes' => 'Urgent restocking order for developer laptops.',
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $po1->id,
            'inventory_item_id' => $itemLaptop->id,
            'quantity' => 10,
            'unit_price' => 1850.00,
            'subtotal' => 18500.00,
        ]);

        // 4. Seed Demo Tenant Organization 2: Nexus Global (Proves Complete Data Isolation!)
        $nexusOrg = Organization::firstOrCreate(
            ['code' => 'nexus'],
            [
                'name' => 'Nexus Global Corp',
                'email' => 'info@nexusglobal.com',
                'phone' => '+1 (555) 999-8877',
                'address' => '500 Silicon Valley Way',
                'status' => 'active',
            ]
        );

        User::firstOrCreate(
            ['email' => 'admin@nexusglobal.com'],
            [
                'organization_id' => $nexusOrg->id,
                'name' => 'Nexus Admin',
                'password' => Hash::make('password'),
                'is_super_admin' => false,
                'is_org_admin' => true,
                'status' => 'active',
            ]
        );

        $catNexus = Category::create(['organization_id' => $nexusOrg->id, 'name' => 'Medical Equipment', 'description' => 'Nexus Org category']);
        InventoryItem::create([
            'organization_id' => $nexusOrg->id,
            'category_id' => $catNexus->id,
            'sku' => 'NEX-MED01',
            'name' => 'Ultrasound Scanner Pro',
            'description' => 'Nexus Org isolated inventory item',
            'unit' => 'pcs',
            'unit_cost' => 12500.00,
            'reorder_level' => 2,
            'current_stock' => 8,
            'status' => 'active',
        ]);
    }
}
