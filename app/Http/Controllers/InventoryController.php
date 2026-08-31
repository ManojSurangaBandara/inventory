<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\WarehouseType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class InventoryController extends Controller
{
    /**
     * Item Master Catalog
     */
    public function items(Request $request)
    {
        $query = InventoryItem::with(['category1', 'category2', 'category3', 'category4'])->latest();

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('category_2_id')) {
            $query->where('category_2_id', $request->category_2_id);
        }

        if ($request->filled('category_3_id')) {
            $query->where('category_3_id', $request->category_3_id);
        }

        if ($request->filled('category_4_id')) {
            $query->where('category_4_id', $request->category_4_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->boolean('low_stock')) {
            $query->whereColumn('current_stock', '<=', 'reorder_level');
        }

        $items = $query->paginate(15);

        // Load Category 1..4 data for cascading selectors
        $category1List = Category::category1()->orderBy('name')->get();
        $category2List = Category::category2()->with('parent')->orderBy('name')->get();
        $category3List = Category::category3()->with('parent')->orderBy('name')->get();
        $category4List = Category::category4()->with('parent')->orderBy('name')->get();

        return view('inventory.items', compact('items', 'category1List', 'category2List', 'category3List', 'category4List'));
    }

    public function storeItem(Request $request)
    {
        $request->validate([
            'sku' => 'required|string|max:100',
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id', // Category 1 (Required)
            'category_2_id' => 'nullable|exists:categories,id', // Category 2 (Optional)
            'category_3_id' => 'nullable|exists:categories,id', // Category 3 (Optional)
            'category_4_id' => 'nullable|exists:categories,id', // Category 4 (Optional)
            'unit' => 'required|string|max:50',
            'unit_cost' => 'required|numeric|min:0',
            'reorder_level' => 'required|integer|min:0',
            'description' => 'nullable|string',
        ]);

        $item = InventoryItem::create([
            'organization_id' => Auth::user()->organization_id,
            'category_id' => $request->category_id,
            'category_2_id' => $request->category_2_id,
            'category_3_id' => $request->category_3_id,
            'category_4_id' => $request->category_4_id,
            'sku' => strtoupper($request->sku),
            'name' => $request->name,
            'description' => $request->description,
            'unit' => $request->unit,
            'unit_cost' => $request->unit_cost,
            'reorder_level' => $request->reorder_level,
            'current_stock' => 0, // Initial stock is always 0 until added via approved stock requests
            'status' => 'active',
        ]);

        return redirect()->route('inventory.items')->with('success', "Master Item '{$item->name}' ({$item->sku}) registered in catalog with 0 opening stock.");
    }

    public function updateItem(Request $request, int $id)
    {
        $item = InventoryItem::findOrFail($id);

        if ($item->isUsed()) {
            return redirect()->route('inventory.items')->with('error', "Item '{$item->name}' cannot be edited because it is in use or has transaction history.");
        }

        $request->validate([
            'sku' => 'required|string|max:100',
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'category_2_id' => 'nullable|exists:categories,id',
            'category_3_id' => 'nullable|exists:categories,id',
            'category_4_id' => 'nullable|exists:categories,id',
            'unit' => 'required|string|max:50',
            'unit_cost' => 'required|numeric|min:0',
            'reorder_level' => 'required|integer|min:0',
            'description' => 'nullable|string',
        ]);

        $item->update([
            'sku' => strtoupper($request->sku),
            'name' => $request->name,
            'category_id' => $request->category_id,
            'category_2_id' => $request->category_2_id,
            'category_3_id' => $request->category_3_id,
            'category_4_id' => $request->category_4_id,
            'unit' => $request->unit,
            'unit_cost' => $request->unit_cost,
            'reorder_level' => $request->reorder_level,
            'description' => $request->description,
        ]);

        return redirect()->route('inventory.items')->with('success', "Item '{$item->name}' updated.");
    }

    public function destroyItem(int $id)
    {
        $item = InventoryItem::findOrFail($id);

        if ($item->isUsed()) {
            return redirect()->route('inventory.items')->with('error', "Item '{$item->name}' cannot be deleted because it is in use or has transaction history.");
        }

        $name = $item->name;
        $item->delete();

        return redirect()->route('inventory.items')->with('success', "Item '{$name}' deleted from catalog.");
    }

    /**
     * Category Master Console (Category 1, 2, 3, 4)
     */
    public function categories()
    {
        $cat1List = Category::category1()->withCount(['children', 'items'])->orderBy('name')->get();
        $cat2List = Category::category2()->with(['parent'])->withCount(['children'])->orderBy('name')->get();
        $cat3List = Category::category3()->with(['parent.parent'])->withCount(['children'])->orderBy('name')->get();
        $cat4List = Category::category4()->with(['parent.parent.parent'])->orderBy('name')->get();

        return view('inventory.categories', compact('cat1List', 'cat2List', 'cat3List', 'cat4List'));
    }

    public function storeCategory(Request $request)
    {
        $request->validate([
            'level' => 'required|integer|in:1,2,3,4',
            'parent_id' => 'nullable|required_if:level,2,3,4|exists:categories,id',
            'code' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $category = Category::create([
            'organization_id' => Auth::user()->organization_id,
            'parent_id' => $request->level > 1 ? $request->parent_id : null,
            'level' => $request->level,
            'code' => $request->filled('code') ? strtoupper($request->code) : null,
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return redirect()->route('inventory.categories')->with('success', "Category {$request->level} '{$category->name}' created.");
    }

    public function updateCategory(Request $request, int $id)
    {
        $category = Category::findOrFail($id);

        $request->validate([
            'parent_id' => 'nullable|exists:categories,id',
            'code' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $category->update([
            'parent_id' => $category->level > 1 ? $request->parent_id : null,
            'code' => $request->filled('code') ? strtoupper($request->code) : null,
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return redirect()->route('inventory.categories')->with('success', "Category {$category->level} '{$category->name}' updated.");
    }

    public function destroyCategory(int $id)
    {
        $category = Category::findOrFail($id);
        $name = $category->name;
        $category->delete();

        return redirect()->route('inventory.categories')->with('success', "Category '{$name}' deleted.");
    }

    /**
     * AJAX endpoint: Get children categories of a parent category
     */
    public function categoryChildren(int $parentId)
    {
        $children = Category::where('parent_id', $parentId)->orderBy('name')->get();
        return response()->json($children);
    }

    /**
     * Suppliers Master
     */
    public function suppliers()
    {
        $suppliers = Supplier::withCount('purchaseOrders')->get();
        return view('inventory.suppliers', compact('suppliers'));
    }

    public function storeSupplier(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
        ]);

        Supplier::create([
            'organization_id' => Auth::user()->organization_id,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
        ]);

        return redirect()->route('inventory.suppliers')->with('success', "Supplier added.");
    }

    public function destroySupplier(int $id)
    {
        $supplier = Supplier::findOrFail($id);

        $purchaseOrdersCount = $supplier->purchaseOrders()->count();

        if ($purchaseOrdersCount > 0) {
            return redirect()->route('inventory.suppliers')->with('error', "Cannot delete supplier '{$supplier->name}': it is linked to {$purchaseOrdersCount} purchase order(s). Historical transaction records must be preserved.");
        }

        $name = $supplier->name;
        $supplier->delete();

        return redirect()->route('inventory.suppliers')->with('success', "Supplier '{$name}' deleted successfully.");
    }

    /**
     * Warehouses Master
     */
    public function warehouses()
    {
        $orgId = Auth::user()->organization_id;

        // Ensure default baseline warehouse types exist
        $warehouseTypes = WarehouseType::ensureDefaults($orgId);

        $warehousesQuery = Warehouse::where('organization_id', $orgId)
            ->with(['warehouseType'])
            ->withCount([
                'stockMovements',
                'purchaseOrders',
            ]);

        if (Schema::hasTable('warehouse_stocks')) {
            $warehousesQuery->withCount('stocks')->with(['stocks']);
        }

        $warehouses = $warehousesQuery->get();

        return view('inventory.warehouses', compact('warehouses', 'warehouseTypes'));
    }

    public function storeWarehouse(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'warehouse_type_id' => 'nullable|exists:warehouse_types,id',
            'type' => 'nullable|string|max:50',
            'location' => 'nullable|string',
        ];

        $request->validate($rules);

        $typeString = 'main';
        $typeId = $request->warehouse_type_id ?: null;

        if ($typeId) {
            $whType = WarehouseType::where('organization_id', Auth::user()->organization_id)->find($typeId);
            if ($whType) {
                $typeString = strtolower($whType->code);
            }
        } elseif ($request->filled('type')) {
            $typeString = strtolower($request->type);
        }

        Warehouse::create([
            'organization_id' => Auth::user()->organization_id,
            'warehouse_type_id' => $typeId,
            'name' => $request->name,
            'code' => strtoupper($request->code),
            'type' => $typeString,
            'location' => $request->location,
        ]);

        return redirect()->route('inventory.warehouses')->with('success', "Warehouse added successfully.");
    }

    public function updateWarehouse(Request $request, int $id)
    {
        $warehouse = Warehouse::where('organization_id', Auth::user()->organization_id)->findOrFail($id);

        $rules = [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'warehouse_type_id' => 'nullable|exists:warehouse_types,id',
            'type' => 'nullable|string|max:50',
            'location' => 'nullable|string',
        ];

        $request->validate($rules);

        $typeString = $warehouse->type ?? 'main';
        $typeId = $request->warehouse_type_id ?: null;

        if ($typeId) {
            $whType = WarehouseType::where('organization_id', Auth::user()->organization_id)->find($typeId);
            if ($whType) {
                $typeString = strtolower($whType->code);
            }
        } elseif ($request->filled('type')) {
            $typeString = strtolower($request->type);
        }

        $warehouse->update([
            'name' => $request->name,
            'code' => strtoupper($request->code),
            'warehouse_type_id' => $typeId,
            'type' => $typeString,
            'location' => $request->location,
        ]);

        return redirect()->route('inventory.warehouses')->with('success', "Warehouse '{$warehouse->name}' updated successfully.");
    }

    public function destroyWarehouse(int $id)
    {
        $warehouse = Warehouse::where('organization_id', Auth::user()->organization_id)->findOrFail($id);

        $originMovementsCount = $warehouse->stockMovements()->count();
        $targetMovementsCount = \App\Models\StockMovement::where('target_warehouse_id', $warehouse->id)->count();
        $stockMovementsCount = $originMovementsCount + $targetMovementsCount;
        $purchaseOrdersCount = $warehouse->purchaseOrders()->count();

        if ($stockMovementsCount > 0 || $purchaseOrdersCount > 0) {
            $reasons = [];
            if ($stockMovementsCount > 0) {
                $reasons[] = "{$stockMovementsCount} stock movement(s)";
            }
            if ($purchaseOrdersCount > 0) {
                $reasons[] = "{$purchaseOrdersCount} purchase order(s)";
            }
            $reasonText = implode(' and ', $reasons);

            return redirect()->route('inventory.warehouses')->with('error', "Cannot delete warehouse '{$warehouse->name}': it is linked to {$reasonText}. Historical transaction records must be preserved.");
        }

        $name = $warehouse->name;
        $warehouse->delete();

        return redirect()->route('inventory.warehouses')->with('success', "Warehouse '{$name}' deleted successfully.");
    }

    /**
     * Warehouse Types CRUD
     */
    public function storeWarehouseType(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'color' => 'required|string|in:emerald,blue,amber,purple,rose,cyan,indigo',
            'description' => 'nullable|string|max:500',
        ]);

        $code = strtoupper(trim($request->code));
        $orgId = Auth::user()->organization_id;

        $exists = WarehouseType::where('organization_id', $orgId)->where('code', $code)->exists();
        if ($exists) {
            return redirect()->route('inventory.warehouses')->with('error', "A warehouse type with code '{$code}' already exists.");
        }

        WarehouseType::create([
            'organization_id' => $orgId,
            'name' => $request->name,
            'code' => $code,
            'color' => $request->color,
            'description' => $request->description,
            'is_default' => false,
        ]);

        return redirect()->route('inventory.warehouses')->with('success', "Warehouse type '{$request->name}' created successfully.");
    }

    public function updateWarehouseType(Request $request, int $id)
    {
        $orgId = Auth::user()->organization_id;
        $warehouseType = WarehouseType::where('organization_id', $orgId)->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'color' => 'required|string|in:emerald,blue,amber,purple,rose,cyan,indigo',
            'description' => 'nullable|string|max:500',
        ]);

        $code = strtoupper(trim($request->code));

        $exists = WarehouseType::where('organization_id', $orgId)
            ->where('code', $code)
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return redirect()->route('inventory.warehouses')->with('error', "Another warehouse type with code '{$code}' already exists.");
        }

        $warehouseType->update([
            'name' => $request->name,
            'code' => $code,
            'color' => $request->color,
            'description' => $request->description,
        ]);

        return redirect()->route('inventory.warehouses')->with('success', "Warehouse type '{$warehouseType->name}' updated successfully.");
    }

    public function destroyWarehouseType(int $id)
    {
        $orgId = Auth::user()->organization_id;
        $warehouseType = WarehouseType::where('organization_id', $orgId)->findOrFail($id);

        $linkedCount = $warehouseType->warehouses()->count();
        if ($linkedCount > 0) {
            return redirect()->route('inventory.warehouses')->with('error', "Cannot delete warehouse type '{$warehouseType->name}': {$linkedCount} warehouse(s) are currently configured under this type.");
        }

        $name = $warehouseType->name;
        $warehouseType->delete();

        return redirect()->route('inventory.warehouses')->with('success', "Warehouse type '{$name}' deleted successfully.");
    }
}
