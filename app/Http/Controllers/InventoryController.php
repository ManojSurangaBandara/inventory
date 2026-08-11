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
     * Inventory Items
     */
    public function items(Request $request)
    {
        $query = InventoryItem::with(['category'])->latest();

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->boolean('low_stock')) {
            $query->whereColumn('current_stock', '<=', 'reorder_level');
        }

        $items = $query->paginate(15);
        $categories = Category::all();

        return view('inventory.items', compact('items', 'categories'));
    }

    public function storeItem(Request $request)
    {
        $request->validate([
            'sku' => 'required|string|max:100',
            'name' => 'required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'unit' => 'required|string|max:50',
            'unit_cost' => 'required|numeric|min:0',
            'reorder_level' => 'required|integer|min:0',
            'current_stock' => 'required|integer|min:0',
            'description' => 'nullable|string',
        ]);

        $item = InventoryItem::create([
            'organization_id' => Auth::user()->organization_id,
            'category_id' => $request->category_id,
            'sku' => strtoupper($request->sku),
            'name' => $request->name,
            'description' => $request->description,
            'unit' => $request->unit,
            'unit_cost' => $request->unit_cost,
            'reorder_level' => $request->reorder_level,
            'current_stock' => $request->current_stock,
            'status' => 'active',
        ]);

        return redirect()->route('inventory.items')->with('success', "Item '{$item->name}' created.");
    }

    public function updateItem(Request $request, int $id)
    {
        $item = InventoryItem::findOrFail($id);

        $request->validate([
            'sku' => 'required|string|max:100',
            'name' => 'required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'unit' => 'required|string|max:50',
            'unit_cost' => 'required|numeric|min:0',
            'reorder_level' => 'required|integer|min:0',
            'current_stock' => 'required|integer|min:0',
            'description' => 'nullable|string',
        ]);

        $item->update([
            'sku' => strtoupper($request->sku),
            'name' => $request->name,
            'category_id' => $request->category_id,
            'unit' => $request->unit,
            'unit_cost' => $request->unit_cost,
            'reorder_level' => $request->reorder_level,
            'current_stock' => $request->current_stock,
            'description' => $request->description,
        ]);

        return redirect()->route('inventory.items')->with('success', "Item '{$item->name}' updated.");
    }

    public function destroyItem(int $id)
    {
        $item = InventoryItem::findOrFail($id);
        $item->delete();

        return redirect()->route('inventory.items')->with('success', "Item deleted.");
    }

    /**
     * Categories
     */
    public function categories()
    {
        $categories = Category::withCount('items')->get();
        return view('inventory.categories', compact('categories'));
    }

    public function storeCategory(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        Category::create([
            'organization_id' => Auth::user()->organization_id,
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return redirect()->route('inventory.categories')->with('success', "Category created.");
    }

    /**
     * Suppliers
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
     * Warehouses
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
