<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\StockMovement;
use App\Models\StockMovementItem;
use App\Models\Warehouse;
use App\Models\WorkflowLog;
use App\Services\NotificationService;
use App\Services\WorkflowService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StockMovementController extends Controller
{
    protected WorkflowService $workflowService;
    protected NotificationService $notificationService;

    public function __construct(WorkflowService $workflowService, NotificationService $notificationService)
    {
        $this->workflowService = $workflowService;
        $this->notificationService = $notificationService;
    }

    public function index()
    {
        $user = Auth::user();
        $movementsQuery = StockMovement::with(['items.item', 'item', 'warehouse', 'targetWarehouse', 'creator', 'workflowDefinition'])
            ->latest();

        if ($user->isWarehouseScoped()) {
            $movementsQuery->where(function ($q) use ($user) {
                $q->where('warehouse_id', $user->warehouse_id)
                  ->orWhere('target_warehouse_id', $user->warehouse_id);
            });
        }

        $movements = $movementsQuery->get();
        $items = InventoryItem::all();
        $warehouses = Warehouse::where('organization_id', $user->organization_id)->orderBy('type')->orderBy('name')->get();

        // Get default workflow definition
        $workflow = $this->workflowService->getActiveWorkflow('StockMovement', $user->organization_id, $user->warehouse_id)
            ?? $this->workflowService->getActiveWorkflow('StockDispatch', $user->organization_id, $user->warehouse_id)
            ?? $this->workflowService->getActiveWorkflow('StockReceipt', $user->organization_id, $user->warehouse_id);

        $availableTransitionsMap = [];
        $stateDetailsMap = [];

        // Build state details across all active workflows for this org
        $allWorkflows = \App\Models\WorkflowDefinition::where('organization_id', $user->organization_id)
            ->where('is_active', true)
            ->with('states')
            ->get();

        foreach ($allWorkflows as $wf) {
            foreach ($wf->states as $st) {
                $stateDetailsMap[$st->code] = $st;
            }
        }

        foreach ($movements as $m) {
            $availableTransitionsMap[$m->id] = $this->workflowService->getAvailableTransitions($m, $user);
        }

        return view('stock.index', compact('movements', 'items', 'warehouses', 'workflow', 'availableTransitionsMap', 'stateDetailsMap'));
    }

    public function transfers()
    {
        $transfers = StockMovement::where('type', 'transfer')
            ->with(['items.item', 'item', 'warehouse', 'targetWarehouse', 'creator', 'workflowDefinition'])
            ->latest()
            ->get();
        $items = InventoryItem::all();
        $warehouses = Warehouse::all();
        $workflow = $this->workflowService->getActiveWorkflowForType('transfer', Auth::user()->organization_id);

        $availableTransitionsMap = [];
        $stateDetailsMap = [];

        if ($workflow) {
            foreach ($workflow->states as $st) {
                $stateDetailsMap[$st->code] = $st;
            }
        }

        foreach ($transfers as $t) {
            $availableTransitionsMap[$t->id] = $this->workflowService->getAvailableTransitions($t, Auth::user());
        }

        return view('stock.transfers', compact('transfers', 'items', 'warehouses', 'workflow', 'availableTransitionsMap', 'stateDetailsMap'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:inbound,outbound,transfer,adjustment',
            'warehouse_id' => 'required|exists:warehouses,id',
            'target_warehouse_id' => 'nullable|required_if:type,transfer|exists:warehouses,id',
            'notes' => 'nullable|string',
            'items' => 'nullable|array|min:1',
            'items.*.inventory_item_id' => 'required_with:items|exists:inventory_items,id',
            'items.*.quantity' => 'required_with:items|integer|min:1',
            'items.*.item_lot_number' => 'nullable|string|max:100',
            // Legacy single item fields support
            'inventory_item_id' => 'nullable|exists:inventory_items,id',
            'quantity' => 'nullable|integer|min:1',
            'item_lot_number' => 'nullable|string|max:100',
        ]);

        $workflow = $this->workflowService->getActiveWorkflowForType($request->type, Auth::user()->organization_id);
        $initialStateCode = $workflow && $workflow->initialState() ? $workflow->initialState()->code : 'draft';

        $refPrefix = match ($request->type) {
            'inbound' => 'SM-ADD-',
            'transfer' => 'SM-TRF-',
            'adjustment' => 'SM-ADJ-',
            default => 'SM-REQ-',
        };
        $refCode = $refPrefix . strtoupper(Str::random(6));

        // Prepare line items list
        $itemsList = [];
        if ($request->has('items') && is_array($request->items)) {
            foreach ($request->items as $itemData) {
                if (!empty($itemData['inventory_item_id']) && !empty($itemData['quantity'])) {
                    $lot = !empty($itemData['item_lot_number']) ? $itemData['item_lot_number'] : ('LOT-' . date('Ymd') . '-' . rand(100, 999));
                    $itemsList[] = [
                        'inventory_item_id' => $itemData['inventory_item_id'],
                        'quantity' => (int) $itemData['quantity'],
                        'item_lot_number' => $lot,
                    ];
                }
            }
        } elseif ($request->filled('inventory_item_id') && $request->filled('quantity')) {
            $lot = $request->input('item_lot_number') ?: ('LOT-' . date('Ymd') . '-' . rand(100, 999));
            $itemsList[] = [
                'inventory_item_id' => $request->inventory_item_id,
                'quantity' => (int) $request->quantity,
                'item_lot_number' => $lot,
            ];
        }

        if (empty($itemsList)) {
            return redirect()->back()->withErrors(['items' => 'At least one inventory item must be added to the stock request.'])->withInput();
        }

        $movement = DB::transaction(function () use ($request, $refCode, $initialStateCode, $itemsList, $workflow) {
            $firstItem = $itemsList[0];
            $lotsSummary = implode(', ', array_unique(array_column($itemsList, 'item_lot_number')));

            $sm = StockMovement::create([
                'organization_id' => Auth::user()->organization_id,
                'reference_code' => $refCode,
                'type' => $request->type,
                'warehouse_id' => $request->warehouse_id,
                'target_warehouse_id' => $request->target_warehouse_id,
                'inventory_item_id' => $firstItem['inventory_item_id'],
                'quantity' => array_sum(array_column($itemsList, 'quantity')),
                'item_lot_number' => $lotsSummary,
                'source_system' => 'manual',
                'current_state' => $initialStateCode,
                'workflow_definition_id' => $workflow?->id,
                'created_by' => Auth::id(),
                'notes' => $request->notes,
            ]);

            foreach ($itemsList as $row) {
                StockMovementItem::create([
                    'organization_id' => Auth::user()->organization_id,
                    'stock_movement_id' => $sm->id,
                    'inventory_item_id' => $row['inventory_item_id'],
                    'quantity' => $row['quantity'],
                    'item_lot_number' => $row['item_lot_number'],
                ]);
            }

            // Log initial creation
            WorkflowLog::create([
                'organization_id' => Auth::user()->organization_id,
                'entity_type' => 'StockMovement',
                'entity_id' => $sm->id,
                'from_state' => null,
                'to_state' => $initialStateCode,
                'action' => 'Created Stock Requisition (' . count($itemsList) . ' items)',
                'user_id' => Auth::id(),
                'notes' => "Initiated batch request with " . count($itemsList) . " line item(s). Lots: {$lotsSummary}",
            ]);

            return $sm;
        });

        // Trigger notification to Level 1 approvers (e.g., OC)
        $this->notificationService->sendToRole(
            Auth::user()->organization_id,
            'oc',
            "New Multi-Item Stock Request: {$refCode}",
            "Subject Clerk / User created stock request {$refCode} containing " . count($itemsList) . " item lot(s). Awaiting OC approval.",
            'approval_needed',
            route('stock.show', $movement->id)
        );

        return redirect()->route('stock.index')->with('success', "Stock Request '{$refCode}' with " . count($itemsList) . " item(s) created successfully.");
    }

    public function transition(Request $request, int $id)
    {
        $request->validate([
            'transition_id' => 'required|integer',
            'notes' => 'nullable|string',
        ]);

        $movement = StockMovement::findOrFail($id);

        try {
            $this->workflowService->executeTransition($movement, $request->transition_id, Auth::user(), $request->notes);
            return redirect()->back()->with('success', "Workflow state updated for request '{$movement->reference_code}'.");
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function show(int $id)
    {
        $movement = StockMovement::with(['items.item', 'item', 'warehouse', 'targetWarehouse', 'creator', 'workflowDefinition'])->findOrFail($id);
        $logs = WorkflowLog::where('entity_type', 'StockMovement')
            ->where('entity_id', $movement->id)
            ->with('user')
            ->latest()
            ->get();

        $workflow = $this->workflowService->getWorkflowForEntity($movement);
        $availableTransitions = $this->workflowService->getAvailableTransitions($movement, Auth::user());

        return view('stock.show', compact('movement', 'logs', 'workflow', 'availableTransitions'));
    }

    /**
     * View Current Stock Balance across items and warehouses with metrics & filters.
     */
    public function stockBalance(Request $request)
    {
        $user = Auth::user();
        $activeWarehouseId = null;

        // If user is assigned to a specific warehouse, scope to it
        if ($user->isWarehouseScoped()) {
            $activeWarehouseId = $user->warehouse_id;
        } elseif ($request->filled('warehouse_id')) {
            $activeWarehouseId = (int) $request->warehouse_id;
        }

        $activeWarehouse = $activeWarehouseId ? Warehouse::with('warehouseType')->find($activeWarehouseId) : null;
        $warehouses = Warehouse::where('organization_id', $user->organization_id)->with('warehouseType')->orderBy('name')->get();

        $withRelations = ['category1', 'category2', 'category3', 'category4'];
        if (\Illuminate\Support\Facades\Schema::hasTable('warehouse_stocks')) {
            $withRelations[] = 'warehouseStocks';
        }
        $query = InventoryItem::with($withRelations);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Apply warehouse-specific filtering if active
        if ($activeWarehouseId) {
            if ($request->filled('stock_status')) {
                if ($request->stock_status === 'in_stock') {
                    $query->whereHas('warehouseStocks', function ($q) use ($activeWarehouseId) {
                        $q->where('warehouse_id', $activeWarehouseId)->where('current_stock', '>', 0);
                    });
                } elseif ($request->stock_status === 'low_stock') {
                    $query->whereHas('warehouseStocks', function ($q) use ($activeWarehouseId) {
                        $q->where('warehouse_id', $activeWarehouseId)
                          ->where('current_stock', '>', 0)
                          ->whereColumn('current_stock', '<=', 'reorder_level');
                    });
                } elseif ($request->stock_status === 'out_of_stock') {
                    $query->where(function ($q) use ($activeWarehouseId) {
                        $q->whereDoesntHave('warehouseStocks', function ($sub) use ($activeWarehouseId) {
                            $sub->where('warehouse_id', $activeWarehouseId)->where('current_stock', '>', 0);
                        });
                    });
                }
            }
        } else {
            if ($request->filled('stock_status')) {
                if ($request->stock_status === 'in_stock') {
                    $query->where('current_stock', '>', 0);
                } elseif ($request->stock_status === 'low_stock') {
                    $query->whereColumn('current_stock', '<=', 'reorder_level')
                          ->where('current_stock', '>', 0);
                } elseif ($request->stock_status === 'out_of_stock') {
                    $query->where('current_stock', '<=', 0);
                }
            }
        }

        // Sorting
        $sort = $request->get('sort', 'stock_desc');
        match ($sort) {
            'stock_asc' => $query->orderBy('current_stock', 'asc'),
            'name_asc' => $query->orderBy('name', 'asc'),
            'sku_asc' => $query->orderBy('sku', 'asc'),
            'valuation_desc' => $query->orderByRaw('current_stock * unit_cost DESC'),
            default => $query->orderBy('current_stock', 'desc')->orderBy('name', 'asc'),
        };

        $items = $query->paginate(15)->withQueryString();

        // Calculate summary KPI stats
        $allItems = InventoryItem::with('warehouseStocks')->get();
        if ($activeWarehouseId) {
            $totalUnits = 0;
            $totalValuation = 0;
            $inStockCount = 0;
            $lowStockCount = 0;
            $outOfStockCount = 0;

            foreach ($allItems as $item) {
                $whStock = (float) $item->stockInWarehouse($activeWarehouseId);
                $totalUnits += $whStock;
                $totalValuation += ($whStock * $item->unit_cost);

                if ($whStock > $item->reorder_level) {
                    $inStockCount++;
                } elseif ($whStock > 0 && $whStock <= $item->reorder_level) {
                    $lowStockCount++;
                } else {
                    $outOfStockCount++;
                }
            }
        } else {
            $totalUnits = $allItems->sum('current_stock');
            $totalValuation = $allItems->sum(fn($i) => $i->current_stock * $i->unit_cost);
            $inStockCount = $allItems->where('current_stock', '>', 0)->count();
            $lowStockCount = $allItems->filter(fn($i) => $i->current_stock <= $i->reorder_level && $i->current_stock > 0)->count();
            $outOfStockCount = $allItems->where('current_stock', '<=', 0)->count();
        }

        $categories = \App\Models\Category::category1()->orderBy('name')->get();

        return view('stock.balance', compact(
            'items',
            'totalUnits',
            'totalValuation',
            'inStockCount',
            'lowStockCount',
            'outOfStockCount',
            'categories',
            'warehouses',
            'activeWarehouse',
            'activeWarehouseId'
        ));
    }
}
