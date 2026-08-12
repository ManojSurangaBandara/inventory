<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Models\WorkflowDefinition;
use App\Services\NotificationService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ItemRequestApiController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Create item request from external Workshop Management System.
     */
    public function store(Request $request)
    {
        $request->validate([
            'sku' => 'required_without:inventory_item_id|string',
            'inventory_item_id' => 'required_without:sku|integer',
            'quantity' => 'required|numeric|min:1',
            'lot_number' => 'nullable|string|max:100',
            'warehouse_id' => 'nullable|integer',
            'notes' => 'nullable|string|max:500',
        ]);

        $orgId = $request->input('api_organization_id');

        // Resolve inventory item
        $itemQuery = InventoryItem::where('organization_id', $orgId);
        if ($request->filled('inventory_item_id')) {
            $itemQuery->where('id', $request->input('inventory_item_id'));
        } else {
            $itemQuery->where('sku', $request->input('sku'));
        }
        $item = $itemQuery->first();

        if (!$item) {
            return response()->json([
                'success' => false,
                'error' => 'Inventory item not found for specified SKU or ID.'
            ], 404);
        }

        // Resolve warehouse
        $warehouse = null;
        if ($request->filled('warehouse_id')) {
            $warehouse = Warehouse::where('organization_id', $orgId)->find($request->input('warehouse_id'));
        }
        if (!$warehouse) {
            $warehouse = Warehouse::where('organization_id', $orgId)->first();
        }

        if (!$warehouse) {
            return response()->json([
                'success' => false,
                'error' => 'No active warehouse configured for tenant organization.'
            ], 400);
        }

        // Resolve initial workflow state for StockMovement
        $workflow = WorkflowDefinition::where('organization_id', $orgId)
            ->where('entity_type', 'StockMovement')
            ->where('is_active', true)
            ->with(['states', 'transitions'])
            ->first();

        $initialStateCode = 'draft';
        if ($workflow) {
            $initialState = $workflow->states->firstWhere('is_initial', true) ?? $workflow->states->first();
            if ($initialState) {
                $initialStateCode = $initialState->code;
            }
        }

        $referenceCode = 'REQ-WMS-' . strtoupper(Str::random(6));

        $movement = StockMovement::create([
            'organization_id' => $orgId,
            'reference_code' => $referenceCode,
            'type' => 'outbound',
            'warehouse_id' => $warehouse->id,
            'inventory_item_id' => $item->id,
            'quantity' => $request->input('quantity'),
            'item_lot_number' => $request->input('lot_number', 'LOT-API-' . rand(100, 999)),
            'source_system' => 'workshop_api',
            'current_state' => $initialStateCode,
            'created_by' => null, // Created via API
            'notes' => 'API Request from Workshop Management System: ' . ($request->input('notes') ?? 'Standard item issue request.'),
        ]);

        // Send notification to Level 1 approvers (e.g. OC or Org Admins)
        $this->notificationService->sendToRole(
            $orgId,
            'oc',
            "New Workshop API Item Request: {$referenceCode}",
            "Workshop Management System requested {$movement->quantity} x {$item->name} (Lot: {$movement->item_lot_number}). Awaiting OC approval.",
            'approval_needed',
            route('stock.show', $movement->id)
        );

        return response()->json([
            'success' => true,
            'message' => 'Item request created successfully and entered approval pipeline.',
            'data' => [
                'id' => $movement->id,
                'reference_code' => $movement->reference_code,
                'item_sku' => $item->sku,
                'item_name' => $item->name,
                'quantity' => $movement->quantity,
                'lot_number' => $movement->item_lot_number,
                'source_system' => $movement->source_system,
                'current_state' => $movement->current_state,
                'created_at' => $movement->created_at->toIso8601String(),
            ]
        ], 201);
    }
}
