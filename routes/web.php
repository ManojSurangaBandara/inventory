<?php

use App\Http\Controllers\ApiTokenController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrgAdminController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\WorkflowController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/', function () {
    return redirect()->route('login');
});

/*
|--------------------------------------------------------------------------
| Authenticated App Routes (Tenant Scoped & Super Admin)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'tenant.context'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Notifications Routes
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.readAll');

    /*
    |--------------------------------------------------------------------------
    | Super Admin Routes (No Organization / Global Scope)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['superadmin'])->prefix('superadmin')->as('superadmin.')->group(function () {
        Route::get('/dashboard', [SuperAdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/organizations', [SuperAdminController::class, 'organizations'])->name('organizations');
        Route::post('/organizations', [SuperAdminController::class, 'storeOrganization'])->name('organizations.store');
        Route::post('/organizations/{id}/admin', [SuperAdminController::class, 'createOrgAdmin'])->name('organizations.admin.store');
        Route::post('/organizations/{id}/toggle', [SuperAdminController::class, 'toggleStatus'])->name('organizations.toggle');
    });

    /*
    |--------------------------------------------------------------------------
    | Organization Admin Routes (Users, Custom Roles, API Tokens, UI Workflows)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['orgadmin'])->prefix('admin')->as('orgadmin.')->group(function () {
        // User management
        Route::get('/users', [OrgAdminController::class, 'users'])->name('users');
        Route::post('/users', [OrgAdminController::class, 'storeUser'])->name('users.store');
        Route::put('/users/{id}', [OrgAdminController::class, 'updateUser'])->name('users.update');

        // Role & Permission management
        Route::get('/roles', [OrgAdminController::class, 'roles'])->name('roles');
        Route::post('/roles', [OrgAdminController::class, 'storeRole'])->name('roles.store');
        Route::put('/roles/{id}', [OrgAdminController::class, 'updateRole'])->name('roles.update');

        // API Token management (Workshop Management System)
        Route::get('/tokens', [ApiTokenController::class, 'index'])->name('tokens');
        Route::post('/tokens', [ApiTokenController::class, 'store'])->name('tokens.store');
        Route::delete('/tokens/{id}', [ApiTokenController::class, 'destroy'])->name('tokens.destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | UI Workflow Builder Routes (Org Admin)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['orgadmin'])->prefix('workflows')->as('workflows.')->group(function () {
        Route::get('/', [WorkflowController::class, 'index'])->name('index');
        Route::post('/definitions', [WorkflowController::class, 'storeDefinition'])->name('store');
        Route::delete('/{id}', [WorkflowController::class, 'destroyDefinition'])->name('destroy');
        Route::get('/{id}/builder', [WorkflowController::class, 'builder'])->name('builder');
        Route::post('/{id}/states', [WorkflowController::class, 'storeState'])->name('states.store');
        Route::post('/{id}/states/reorder', [WorkflowController::class, 'reorderStates'])->name('states.reorder');
        Route::put('/states/{id}', [WorkflowController::class, 'updateState'])->name('states.update');
        Route::delete('/states/{id}', [WorkflowController::class, 'deleteState'])->name('states.delete');
        Route::post('/{id}/transitions', [WorkflowController::class, 'storeTransition'])->name('transitions.store');
        Route::delete('/transitions/{id}', [WorkflowController::class, 'deleteTransition'])->name('transitions.delete');
    });

    /*
    |--------------------------------------------------------------------------
    | Inventory Master Data Routes (Tenant Admin & Super Admin Only)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['orgadmin'])->prefix('inventory')->as('inventory.')->group(function () {
        Route::get('/items', [InventoryController::class, 'items'])->name('items');
        Route::post('/items', [InventoryController::class, 'storeItem'])->name('items.store');
        Route::put('/items/{id}', [InventoryController::class, 'updateItem'])->name('items.update');
        Route::delete('/items/{id}', [InventoryController::class, 'destroyItem'])->name('items.destroy');

        Route::get('/categories', [InventoryController::class, 'categories'])->name('categories');
        Route::post('/categories', [InventoryController::class, 'storeCategory'])->name('categories.store');
        Route::put('/categories/{id}', [InventoryController::class, 'updateCategory'])->name('categories.update');
        Route::delete('/categories/{id}', [InventoryController::class, 'destroyCategory'])->name('categories.destroy');
        Route::get('/categories/{parentId}/children', [InventoryController::class, 'categoryChildren'])->name('categories.children');

        Route::get('/suppliers', [InventoryController::class, 'suppliers'])->name('suppliers');
        Route::post('/suppliers', [InventoryController::class, 'storeSupplier'])->name('suppliers.store');

        Route::get('/warehouses', [InventoryController::class, 'warehouses'])->name('warehouses');
        Route::post('/warehouses', [InventoryController::class, 'storeWarehouse'])->name('warehouses.store');
    });

    /*
    |--------------------------------------------------------------------------
    | Stock Movement & Workflow Action Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('stock')->as('stock.')->group(function () {
        Route::get('/', [StockMovementController::class, 'index'])->name('index');
        Route::post('/', [StockMovementController::class, 'store'])->name('store');
        Route::get('/{id}', [StockMovementController::class, 'show'])->name('show');
        Route::post('/{id}/transition', [StockMovementController::class, 'transition'])->name('transition');
    });

    /*
    |--------------------------------------------------------------------------
    | Purchase Orders & Workflow Action Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('orders')->as('orders.')->group(function () {
        Route::get('/', [PurchaseOrderController::class, 'index'])->name('index');
        Route::post('/', [PurchaseOrderController::class, 'store'])->name('store');
        Route::get('/{id}', [PurchaseOrderController::class, 'show'])->name('show');
        Route::post('/{id}/transition', [PurchaseOrderController::class, 'transition'])->name('transition');
    });
});
