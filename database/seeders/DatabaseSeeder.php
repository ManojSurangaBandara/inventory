<?php

namespace Database\Seeders;

use App\Models\ApiToken;
use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\Notification;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Role;
use App\Models\StockMovement;
use App\Models\StockMovementItem;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowLog;
use App\Models\WorkflowState;
use App\Models\WorkflowTransition;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

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

        // 4. Seed PDF Flowchart Specific Roles for Apex Logistics
        $roleClerk = Role::firstOrCreate(
            ['organization_id' => $apexOrg->id, 'slug' => 'subject-clerk'],
            ['name' => 'Subject Clerk', 'description' => 'Enters items lot to system & creates stock request', 'is_system' => false]
        );
        $roleClerk->permissions()->sync([$permModels['items.view']->id, $permModels['stock.create']->id]);

        $roleOC = Role::firstOrCreate(
            ['organization_id' => $apexOrg->id, 'slug' => 'oc'],
            ['name' => 'OC (Operating Commander)', 'description' => 'First level verification officer (OC Approval)', 'is_system' => false]
        );
        $roleOC->permissions()->sync([$permModels['items.view']->id, $permModels['stock.approve']->id]);

        $roleQM = Role::firstOrCreate(
            ['organization_id' => $apexOrg->id, 'slug' => 'qm'],
            ['name' => 'QM (Quality Manager)', 'description' => 'Second level quality control officer (QM Approval)', 'is_system' => false]
        );
        $roleQM->permissions()->sync([$permModels['items.view']->id, $permModels['stock.approve']->id]);

        $roleCO = Role::firstOrCreate(
            ['organization_id' => $apexOrg->id, 'slug' => 'co'],
            ['name' => 'CO (Commanding Officer)', 'description' => 'Final authorizing officer (CO Approval)', 'is_system' => false]
        );
        $roleCO->permissions()->sync([$permModels['items.view']->id, $permModels['stock.approve']->id]);

        $roleStoremen = Role::firstOrCreate(
            ['organization_id' => $apexOrg->id, 'slug' => 'storemen'],
            ['name' => 'Storemen', 'description' => 'Physical store keeper executing item issues and stock updates', 'is_system' => false]
        );
        $roleStoremen->permissions()->sync([$permModels['items.view']->id, $permModels['stock.dispatch']->id]);

        // Seed Users for each PDF Flowchart Role
        $userClerk = User::firstOrCreate(['email' => 'clerk@apexlogistics.com'], [
            'organization_id' => $apexOrg->id, 'name' => 'Sam Subject Clerk', 'password' => Hash::make('password'), 'is_super_admin' => false, 'is_org_admin' => false, 'status' => 'active'
        ]);
        $userClerk->roles()->sync([$roleClerk->id]);

        $userOC = User::firstOrCreate(['email' => 'oc@apexlogistics.com'], [
            'organization_id' => $apexOrg->id, 'name' => 'Oliver OC Officer', 'password' => Hash::make('password'), 'is_super_admin' => false, 'is_org_admin' => false, 'status' => 'active'
        ]);
        $userOC->roles()->sync([$roleOC->id]);

        $userQM = User::firstOrCreate(['email' => 'qm@apexlogistics.com'], [
            'organization_id' => $apexOrg->id, 'name' => 'Quincy QM Inspector', 'password' => Hash::make('password'), 'is_super_admin' => false, 'is_org_admin' => false, 'status' => 'active'
        ]);
        $userQM->roles()->sync([$roleQM->id]);

        $userCO = User::firstOrCreate(['email' => 'co@apexlogistics.com'], [
            'organization_id' => $apexOrg->id, 'name' => 'Charles CO Commander', 'password' => Hash::make('password'), 'is_super_admin' => false, 'is_org_admin' => false, 'status' => 'active'
        ]);
        $userCO->roles()->sync([$roleCO->id]);

        $userStoremen = User::firstOrCreate(['email' => 'storemen@apexlogistics.com'], [
            'organization_id' => $apexOrg->id, 'name' => 'Steven Storemen', 'password' => Hash::make('password'), 'is_super_admin' => false, 'is_org_admin' => false, 'status' => 'active'
        ]);
        $userStoremen->roles()->sync([$roleStoremen->id]);

        // Seed External API Token for Workshop Management System Integration
        ApiToken::firstOrCreate(
            ['organization_id' => $apexOrg->id, 'name' => 'Workshop Management System Server Integration'],
            ['token' => 'wms_demo_token_apex123', 'status' => 'active']
        );

        // Master Data for Apex Logistics
        $catElectronics = Category::create(['organization_id' => $apexOrg->id, 'name' => 'Electronics & Computing', 'description' => 'Laptops, monitors, components']);
        $catParts = Category::create(['organization_id' => $apexOrg->id, 'name' => 'Industrial Parts', 'description' => 'Cables, sensors, robotics parts']);

        $whMain = Warehouse::create(['organization_id' => $apexOrg->id, 'name' => 'Main Distribution Hub', 'code' => 'WH-MAIN', 'location' => 'Zone A Depot']);

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

        $itemCable = InventoryItem::create([
            'organization_id' => $apexOrg->id,
            'category_id' => $catParts->id,
            'sku' => 'CAB-FIBER10M',
            'name' => 'High-Speed Fiber Optic Cable 10m',
            'description' => 'Duplex Single Mode Optical Patch Cable',
            'unit' => 'pcs',
            'unit_cost' => 24.50,
            'reorder_level' => 25,
            'current_stock' => 50,
            'status' => 'active',
        ]);

        // 5. Seed Pre-configured Workflow 1: "Add Items to Main Stock Workflow" (PDF Page 2 Flowchart)
        $addStockWf = WorkflowDefinition::create([
            'organization_id' => $apexOrg->id,
            'name' => 'Add Items to Main Stock Workflow (OC -> QM -> CO)',
            'entity_type' => 'StockMovement',
            'description' => 'Multi-tier approval pipeline for adding new item lots to main stock',
            'is_active' => true,
        ]);

        $stDraft = WorkflowState::create(['organization_id' => $apexOrg->id, 'workflow_definition_id' => $addStockWf->id, 'code' => 'draft', 'name' => 'Requisition Created (Subject Clerk)', 'color' => 'slate', 'is_initial' => true, 'is_final' => false]);
        $stOC = WorkflowState::create(['organization_id' => $apexOrg->id, 'workflow_definition_id' => $addStockWf->id, 'code' => 'oc_pending', 'name' => 'Awaiting OC Approval', 'color' => 'amber', 'is_initial' => false, 'is_final' => false]);
        $stQM = WorkflowState::create(['organization_id' => $apexOrg->id, 'workflow_definition_id' => $addStockWf->id, 'code' => 'qm_pending', 'name' => 'Awaiting QM Approval', 'color' => 'purple', 'is_initial' => false, 'is_final' => false]);
        $stCO = WorkflowState::create(['organization_id' => $apexOrg->id, 'workflow_definition_id' => $addStockWf->id, 'code' => 'co_pending', 'name' => 'Awaiting CO Approval', 'color' => 'indigo', 'is_initial' => false, 'is_final' => false]);
        $stCompleted = WorkflowState::create(['organization_id' => $apexOrg->id, 'workflow_definition_id' => $addStockWf->id, 'code' => 'completed', 'name' => 'Approved & Stock Added', 'color' => 'emerald', 'is_initial' => false, 'is_final' => true]);
        $stRejected = WorkflowState::create(['organization_id' => $apexOrg->id, 'workflow_definition_id' => $addStockWf->id, 'code' => 'rejected', 'name' => 'Rejected by Approval Authority', 'color' => 'rose', 'is_initial' => false, 'is_final' => true]);

        // Subject Clerk -> Submit to OC
        WorkflowTransition::create([
            'organization_id' => $apexOrg->id,
            'workflow_definition_id' => $addStockWf->id,
            'from_state_id' => $stDraft->id,
            'to_state_id' => $stOC->id,
            'action_name' => 'Submit Stock Requisition to OC',
            'allowed_roles' => ['subject-clerk', 'inventory-manager'],
            'requires_note' => false,
        ]);

        // OC -> QM (Approved) OR Reject
        WorkflowTransition::create([
            'organization_id' => $apexOrg->id,
            'workflow_definition_id' => $addStockWf->id,
            'from_state_id' => $stOC->id,
            'to_state_id' => $stQM->id,
            'action_name' => 'OC Approved & Forward to QM',
            'allowed_roles' => ['oc'],
            'requires_note' => false,
        ]);
        WorkflowTransition::create([
            'organization_id' => $apexOrg->id,
            'workflow_definition_id' => $addStockWf->id,
            'from_state_id' => $stOC->id,
            'to_state_id' => $stRejected->id,
            'action_name' => 'OC Reject Information',
            'allowed_roles' => ['oc'],
            'requires_note' => true,
        ]);

        // QM -> CO (Approved) OR Reject
        WorkflowTransition::create([
            'organization_id' => $apexOrg->id,
            'workflow_definition_id' => $addStockWf->id,
            'from_state_id' => $stQM->id,
            'to_state_id' => $stCO->id,
            'action_name' => 'QM Approved & Forward to CO',
            'allowed_roles' => ['qm'],
            'requires_note' => false,
        ]);
        WorkflowTransition::create([
            'organization_id' => $apexOrg->id,
            'workflow_definition_id' => $addStockWf->id,
            'from_state_id' => $stQM->id,
            'to_state_id' => $stRejected->id,
            'action_name' => 'QM Reject Information',
            'allowed_roles' => ['qm'],
            'requires_note' => true,
        ]);

        // CO -> Final Approval (Add Items & Send Notification) OR Reject
        WorkflowTransition::create([
            'organization_id' => $apexOrg->id,
            'workflow_definition_id' => $addStockWf->id,
            'from_state_id' => $stCO->id,
            'to_state_id' => $stCompleted->id,
            'action_name' => 'CO Approve & Add Items to Stock',
            'allowed_roles' => ['co'],
            'requires_note' => false,
        ]);
        WorkflowTransition::create([
            'organization_id' => $apexOrg->id,
            'workflow_definition_id' => $addStockWf->id,
            'from_state_id' => $stCO->id,
            'to_state_id' => $stRejected->id,
            'action_name' => 'CO Reject Information',
            'allowed_roles' => ['co'],
            'requires_note' => true,
        ]);

        // Seed Sample Inbound Stock Request (Add items to main stock)
        $smInbound = StockMovement::create([
            'organization_id' => $apexOrg->id,
            'reference_code' => 'SM-ADD-001',
            'type' => 'inbound',
            'warehouse_id' => $whMain->id,
            'inventory_item_id' => $itemLaptop->id,
            'quantity' => 25,
            'item_lot_number' => 'LOT-2026-DEL1, LOT-2026-CAB1',
            'source_system' => 'manual',
            'current_state' => 'oc_pending',
            'created_by' => $userClerk->id,
            'notes' => 'Subject Clerk entered new multi-item lot of XPS Developer laptops and Fiber Cables.',
        ]);

        StockMovementItem::create([
            'organization_id' => $apexOrg->id,
            'stock_movement_id' => $smInbound->id,
            'inventory_item_id' => $itemLaptop->id,
            'quantity' => 15,
            'item_lot_number' => 'LOT-2026-DEL1',
        ]);

        StockMovementItem::create([
            'organization_id' => $apexOrg->id,
            'stock_movement_id' => $smInbound->id,
            'inventory_item_id' => $itemCable->id,
            'quantity' => 10,
            'item_lot_number' => 'LOT-2026-CAB1',
        ]);

        WorkflowLog::create([
            'organization_id' => $apexOrg->id,
            'entity_type' => 'StockMovement',
            'entity_id' => $smInbound->id,
            'from_state' => 'draft',
            'to_state' => 'oc_pending',
            'action' => 'Submit Stock Requisition to OC',
            'user_id' => $userClerk->id,
            'notes' => 'Submitted multi-item lot details to OC for verification.',
        ]);

        // Seed Sample Workshop Management System API Outbound Request (Item Request Process)
        $smOutbound = StockMovement::create([
            'organization_id' => $apexOrg->id,
            'reference_code' => 'REQ-WMS-8812',
            'type' => 'outbound',
            'warehouse_id' => $whMain->id,
            'inventory_item_id' => $itemCable->id,
            'quantity' => 10,
            'item_lot_number' => 'LOT-WMS-55',
            'source_system' => 'workshop_api',
            'current_state' => 'oc_pending',
            'created_by' => null, // Created via API
            'notes' => 'Item Request received via API from Workshop Management System for Robot Assembly Station B.',
        ]);

        StockMovementItem::create([
            'organization_id' => $apexOrg->id,
            'stock_movement_id' => $smOutbound->id,
            'inventory_item_id' => $itemCable->id,
            'quantity' => 10,
            'item_lot_number' => 'LOT-WMS-55',
        ]);

        // Seed Initial Notifications
        Notification::create([
            'organization_id' => $apexOrg->id,
            'user_id' => $userOC->id,
            'title' => 'New Stock Lot Request awaiting OC Approval',
            'message' => 'Subject Clerk submitted request SM-ADD-001 (Lot: LOT-2026-DEL1) for 15 Dell Laptops.',
            'type' => 'approval_needed',
            'link_url' => route('stock.show', $smInbound->id),
            'is_read' => false,
        ]);

        Notification::create([
            'organization_id' => $apexOrg->id,
            'user_id' => $userOC->id,
            'title' => 'Workshop Management System API Item Request',
            'message' => 'Workshop API requested 10 Fiber Optic Cables (REQ-WMS-8812). Awaiting OC verification.',
            'type' => 'approval_needed',
            'link_url' => route('stock.show', $smOutbound->id),
            'is_read' => false,
        ]);

        // 6. Seed Demo Tenant Organization 2: Nexus Global (Proves Complete Data Isolation!)
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
