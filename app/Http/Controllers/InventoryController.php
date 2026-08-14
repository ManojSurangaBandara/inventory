<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        return redirect()->route('inventory.items')->with('success', "Master Item '{$item->name}' updated.");
    }

    public function destroyItem(int $id)
    {
        $item = InventoryItem::findOrFail($id);
        $name = $item->name;
        $item->delete();

        return redirect()->route('inventory.items')->with('success', "Item '{$name}' deleted from master catalog.");
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

    /**
     * Warehouses Master
     */
    public function warehouses()
    {
        $warehouses = Warehouse::all();
        return view('inventory.warehouses', compact('warehouses'));
    }

    public function storeWarehouse(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'location' => 'nullable|string',
        ]);

        Warehouse::create([
            'organization_id' => Auth::user()->organization_id,
            'name' => $request->name,
            'code' => strtoupper($request->code),
            'location' => $request->location,
        ]);

        return redirect()->route('inventory.warehouses')->with('success', "Warehouse added.");
    }
}
