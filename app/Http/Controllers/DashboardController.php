<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\WorkflowDefinition;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->is_super_admin) {
            return redirect()->route('superadmin.dashboard');
        }

        $totalItems = InventoryItem::count();
        $allItems = InventoryItem::with(['category1', 'category2', 'category3', 'category4'])
            ->orderBy('name')
            ->get();

        $lowStockItems = $allItems->filter(function ($item) {
            return $item->current_stock <= $item->reorder_level;
        })->values();

        $totalStockUnits = (int) $allItems->sum('current_stock');
        $totalStockValuation = (float) $allItems->sum(function ($item) {
            return $item->current_stock * $item->unit_cost;
        });

        $outOfStockCount = $allItems->where('current_stock', '<=', 0)->count();
        $lowStockCount = $allItems->filter(function ($item) {
            return $item->current_stock > 0 && $item->current_stock <= $item->reorder_level;
        })->count();
        $healthyStockCount = $allItems->filter(function ($item) {
            return $item->current_stock > $item->reorder_level;
        })->count();

        // Grouping for category distribution chart
        $categoryBreakdown = $allItems->groupBy(function ($item) {
            return $item->category1->name ?? 'Uncategorized';
        })->map(function ($items, $name) {
            return [
                'name' => $name,
                'count' => $items->count(),
                'units' => (int) $items->sum('current_stock'),
                'valuation' => (float) $items->sum(fn($i) => $i->current_stock * $i->unit_cost),
            ];
        })->values();

        $totalOrders = PurchaseOrder::count();
        $totalMovements = StockMovement::count();
        $recentMovements = StockMovement::with(['item', 'warehouse', 'items.item'])->latest()->take(6)->get();

        // Inter-Warehouse Stock Transfer Metrics & Pattern
        $totalTransfers = StockMovement::where('type', 'transfer')->count();
        $pendingTransfers = StockMovement::where('type', 'transfer')->whereNotIn('current_state', ['completed', 'rejected'])->count();
        $recentTransfers = StockMovement::where('type', 'transfer')->with(['items.item', 'item', 'warehouse', 'targetWarehouse'])->latest()->take(4)->get();
        $transferWorkflow = WorkflowDefinition::where('entity_type', 'StockTransfer')->where('organization_id', $user->organization_id)->where('is_active', true)->first()
            ?? WorkflowDefinition::where('entity_type', 'StockMovement')->where('organization_id', $user->organization_id)->where('is_active', true)->first();

        $workflowsCount = WorkflowDefinition::count();

        return view('dashboard', compact(
            'user',
            'totalItems',
            'allItems',
            'lowStockItems',
            'totalStockUnits',
            'totalStockValuation',
            'healthyStockCount',
            'lowStockCount',
            'outOfStockCount',
            'categoryBreakdown',
            'totalOrders',
            'totalMovements',
            'recentMovements',
            'totalTransfers',
            'pendingTransfers',
            'recentTransfers',
            'transferWorkflow',
            'workflowsCount'
        ));
    }
}
