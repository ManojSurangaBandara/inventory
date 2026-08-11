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
        $lowStockItems = InventoryItem::whereColumn('current_stock', '<=', 'reorder_level')->get();
        $totalOrders = PurchaseOrder::count();
        $totalMovements = StockMovement::count();
        $recentMovements = StockMovement::with(['item', 'warehouse'])->latest()->take(6)->get();

        $workflowsCount = WorkflowDefinition::count();

        return view('dashboard', compact(
            'user',
            'totalItems',
            'lowStockItems',
            'totalOrders',
            'totalMovements',
            'recentMovements',
            'workflowsCount'
        ));
    }
}
