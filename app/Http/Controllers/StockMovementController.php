<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Models\WorkflowLog;
use App\Services\NotificationService;
use App\Services\WorkflowService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $movements = StockMovement::with(['item', 'warehouse', 'targetWarehouse', 'creator'])->latest()->get();
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

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:inbound,outbound,transfer,adjustment',
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'target_warehouse_id' => 'nullable|required_if:type,transfer|exists:warehouses,id',
            'quantity' => 'required|integer|min:1',
            'item_lot_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $workflow = $this->workflowService->getActiveWorkflow('StockMovement', Auth::user()->organization_id);
        $initialStateCode = $workflow && $workflow->initialState() ? $workflow->initialState()->code : 'draft';

        $refPrefix = ($request->type === 'inbound') ? 'SM-ADD-' : 'SM-REQ-';
        $refCode = $refPrefix . strtoupper(Str::random(6));

        $lotNumber = $request->input('item_lot_number');
        if (empty($lotNumber)) {
            $lotNumber = 'LOT-' . date('Ymd') . '-' . rand(100, 999);
        }

        $movement = StockMovement::create([
            'organization_id' => Auth::user()->organization_id,
            'reference_code' => $refCode,
            'type' => $request->type,
            'warehouse_id' => $request->warehouse_id,
            'target_warehouse_id' => $request->target_warehouse_id,
            'inventory_item_id' => $request->inventory_item_id,
            'quantity' => $request->quantity,
            'item_lot_number' => $lotNumber,
            'source_system' => 'manual',
            'current_state' => $initialStateCode,
            'created_by' => Auth::id(),
            'notes' => $request->notes,
        ]);

        // Log initial creation
        WorkflowLog::create([
            'organization_id' => Auth::user()->organization_id,
            'entity_type' => 'StockMovement',
            'entity_id' => $movement->id,
            'from_state' => null,
            'to_state' => $initialStateCode,
            'action' => 'Created Stock Requisition',
            'user_id' => Auth::id(),
            'notes' => "Initiated request for Lot: {$movement->item_lot_number}",
        ]);

        // Trigger notification to Level 1 approvers (e.g., OC)
        $this->notificationService->sendToRole(
            Auth::user()->organization_id,
            'oc',
            "New Stock Request Created: {$refCode}",
            "Subject Clerk / User created stock request {$refCode} (Lot: {$lotNumber}). Awaiting OC approval.",
            'approval_needed',
            route('stock.show', $movement->id)
        );

        return redirect()->route('stock.index')->with('success', "Stock Request '{$refCode}' (Lot: {$lotNumber}) created successfully.");
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
        $movement = StockMovement::with(['item', 'warehouse', 'targetWarehouse', 'creator'])->findOrFail($id);
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
