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
        $movements = StockMovement::with(['items.item', 'item', 'warehouse', 'targetWarehouse', 'creator'])->latest()->get();
        $items = InventoryItem::all();
        $warehouses = Warehouse::all();

        // Get workflow definition and state colors for UI rendering
        $workflow = $this->workflowService->getActiveWorkflow('StockMovement', Auth::user()->organization_id);

        $availableTransitionsMap = [];
        $stateDetailsMap = [];

        if ($workflow) {
            foreach ($workflow->states as $st) {
                $stateDetailsMap[$st->code] = $st;
            }
            foreach ($movements as $m) {
                $availableTransitionsMap[$m->id] = $this->workflowService->getAvailableTransitions($m, Auth::user());
            }
        }

        return view('stock.index', compact('movements', 'items', 'warehouses', 'workflow', 'availableTransitionsMap', 'stateDetailsMap'));
    }

    public function transfers()
    {
        $transfers = StockMovement::where('type', 'transfer')
            ->with(['items.item', 'item', 'warehouse', 'targetWarehouse', 'creator'])
            ->latest()
            ->get();
        $items = InventoryItem::all();
        $warehouses = Warehouse::all();
        $workflow = $this->workflowService->getActiveWorkflow('StockTransfer', Auth::user()->organization_id)
            ?? $this->workflowService->getActiveWorkflow('StockMovement', Auth::user()->organization_id);

        $availableTransitionsMap = [];
        $stateDetailsMap = [];

        if ($workflow) {
            foreach ($workflow->states as $st) {
                $stateDetailsMap[$st->code] = $st;
            }
            foreach ($transfers as $t) {
                $availableTransitionsMap[$t->id] = $this->workflowService->getAvailableTransitions($t, Auth::user());
            }
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

        $workflow = $this->workflowService->getActiveWorkflow('StockMovement', Auth::user()->organization_id);
        $initialStateCode = $workflow && $workflow->initialState() ? $workflow->initialState()->code : 'draft';

        $refPrefix = ($request->type === 'inbound') ? 'SM-ADD-' : 'SM-REQ-';
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

        $movement = DB::transaction(function () use ($request, $refCode, $initialStateCode, $itemsList) {
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
        $movement = StockMovement::with(['items.item', 'item', 'warehouse', 'targetWarehouse', 'creator'])->findOrFail($id);
        $logs = WorkflowLog::where('entity_type', 'StockMovement')
            ->where('entity_id', $movement->id)
            ->with('user')
            ->latest()
            ->get();

        $workflow = $this->workflowService->getActiveWorkflow('StockMovement', Auth::user()->organization_id);
        $availableTransitions = $this->workflowService->getAvailableTransitions($movement, Auth::user());

        return view('stock.show', compact('movement', 'logs', 'workflow', 'availableTransitions'));
    }
}
