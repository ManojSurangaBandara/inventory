<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\WorkflowLog;
use App\Services\WorkflowService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PurchaseOrderController extends Controller
{
    protected WorkflowService $workflowService;

    public function __construct(WorkflowService $workflowService)
    {
        $this->workflowService = $workflowService;
    }

    public function index()
    {
        $orders = PurchaseOrder::with(['supplier', 'warehouse', 'creator', 'items'])->latest()->get();
        $suppliers = Supplier::all();
        $warehouses = Warehouse::all();
        $inventoryItems = InventoryItem::all();

        $workflow = $this->workflowService->getActiveWorkflow('PurchaseOrder', Auth::user()->organization_id);

        $availableTransitionsMap = [];
        if ($workflow) {
            foreach ($orders as $po) {
                $availableTransitionsMap[$po->id] = $this->workflowService->getAvailableTransitions($po, Auth::user());
            }
        }

        return view('orders.index', compact('orders', 'suppliers', 'warehouses', 'inventoryItems', 'workflow', 'availableTransitionsMap'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.inventory_item_id' => 'required|exists:inventory_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $workflow = $this->workflowService->getActiveWorkflow('PurchaseOrder', Auth::user()->organization_id);
        $initialStateCode = $workflow && $workflow->initialState() ? $workflow->initialState()->code : 'draft';

        $poCount = PurchaseOrder::count() + 1;
        $poNumber = 'PO-' . date('Ymd') . '-' . str_pad((string)$poCount, 4, '0', STR_PAD_LEFT);

        $totalAmount = 0;
        foreach ($request->items as $itemData) {
            $totalAmount += $itemData['quantity'] * $itemData['unit_price'];
        }

        $order = PurchaseOrder::create([
            'organization_id' => Auth::user()->organization_id,
            'po_number' => $poNumber,
            'supplier_id' => $request->supplier_id,
            'warehouse_id' => $request->warehouse_id,
            'total_amount' => $totalAmount,
            'current_state' => $initialStateCode,
            'created_by' => Auth::id(),
            'notes' => $request->notes,
        ]);

        foreach ($request->items as $itemData) {
            $subtotal = $itemData['quantity'] * $itemData['unit_price'];
            PurchaseOrderItem::create([
                'organization_id' => $order->organization_id,
                'purchase_order_id' => $order->id,
                'inventory_item_id' => $itemData['inventory_item_id'],
                'quantity' => $itemData['quantity'],
                'unit_price' => $itemData['unit_price'],
                'subtotal' => $subtotal,
            ]);
        }

        WorkflowLog::create([
            'organization_id' => Auth::user()->organization_id,
            'entity_type' => 'PurchaseOrder',
            'entity_id' => $order->id,
            'from_state' => null,
            'to_state' => $initialStateCode,
            'action' => 'Created Purchase Order',
            'user_id' => Auth::id(),
            'notes' => 'Purchase Order created.',
        ]);

        return redirect()->route('orders.index')->with('success', "Purchase Order '{$poNumber}' created successfully.");
    }

    public function transition(Request $request, int $id)
    {
        $request->validate([
            'transition_id' => 'required|integer',
            'notes' => 'nullable|string',
        ]);

        $order = PurchaseOrder::findOrFail($id);

        try {
            $this->workflowService->executeTransition($order, $request->transition_id, Auth::user(), $request->notes);
            return redirect()->route('orders.index')->with('success', "Purchase order '{$order->po_number}' transition completed.");
        } catch (Exception $e) {
            return redirect()->route('orders.index')->with('error', $e->getMessage());
        }
    }

    public function show(int $id)
    {
        $order = PurchaseOrder::with(['supplier', 'warehouse', 'creator', 'items.item'])->findOrFail($id);
        $logs = WorkflowLog::where('entity_type', 'PurchaseOrder')
            ->where('entity_id', $order->id)
            ->with('user')
            ->latest()
            ->get();

        $availableTransitions = $this->workflowService->getAvailableTransitions($order, Auth::user());

        return view('orders.show', compact('order', 'logs', 'availableTransitions'));
    }
}
