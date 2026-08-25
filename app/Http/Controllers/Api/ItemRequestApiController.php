<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\StockMovement;
use App\Models\StockMovementItem;
use App\Models\Warehouse;
use App\Models\WorkflowDefinition;
use App\Services\NotificationService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
     * Supports both single item and multi-item payloads.
     */
    public function store(Request $request)
    {
        $request->validate([
            // Multi-item payload
            'items' => 'nullable|array|min:1',
            'items.*.sku' => 'nullable|string',
            'items.*.inventory_item_id' => 'nullable|integer',
            'items.*.quantity' => 'required_with:items|numeric|min:1',
            'items.*.lot_number' => 'nullable|string|max:100',
            // Single-item fallback payload
            'sku' => 'nullable|string',
            'inventory_item_id' => 'nullable|integer',
            'quantity' => 'nullable|numeric|min:1',
            'lot_number' => 'nullable|string|max:100',
            'warehouse_id' => 'nullable|integer',
            'notes' => 'nullable|string|max:500',
        ]);

        $orgId = $request->input('api_organization_id');

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

        // Parse items
        $resolvedItems = [];

        if ($request->has('items') && is_array($request->items)) {
            foreach ($request->items as $index => $itemInput) {
                $itemQuery = InventoryItem::where('organization_id', $orgId);
                if (!empty($itemInput['inventory_item_id'])) {
                    $itemQuery->where('id', $itemInput['inventory_item_id']);
                } elseif (!empty($itemInput['sku'])) {
                    $itemQuery->where('sku', $itemInput['sku']);
                } else {
                    return response()->json([
                        'success' => false,
                        'error' => "Item at index {$index} must provide either 'inventory_item_id' or 'sku'."
                    ], 422);
                }

                $item = $itemQuery->first();
                if (!$item) {
                    return response()->json([
                        'success' => false,
                        'error' => "Inventory item not found for specified SKU or ID at index {$index}."
                    ], 404);
                }

                $resolvedItems[] = [
                    'model' => $item,
                    'quantity' => (int) $itemInput['quantity'],
                    'lot_number' => !empty($itemInput['lot_number']) ? $itemInput['lot_number'] : ('LOT-API-' . rand(100, 999)),
                ];
            }
        } elseif ($request->filled('sku') || $request->filled('inventory_item_id')) {
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

            $resolvedItems[] = [
                'model' => $item,
                'quantity' => (int) ($request->input('quantity') ?? 1),
                'lot_number' => $request->input('lot_number', 'LOT-API-' . rand(100, 999)),
            ];
        }

        if (empty($resolvedItems)) {
            return response()->json([
                'success' => false,
                'error' => 'No valid item line details provided in the request payload.'
            ], 422);
        }

        // Resolve initial workflow state for Outbound Stock Dispatch
        $workflow = WorkflowDefinition::where('organization_id', $orgId)
            ->where('entity_type', 'StockDispatch')
            ->where('is_active', true)
            ->with(['states', 'transitions'])
            ->first()
            ?? WorkflowDefinition::where('organization_id', $orgId)
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
        $firstItem = $resolvedItems[0];
        $totalQty = array_sum(array_column($resolvedItems, 'quantity'));
        $lotsSummary = implode(', ', array_unique(array_column($resolvedItems, 'lot_number')));

        $movement = DB::transaction(function () use ($orgId, $referenceCode, $warehouse, $firstItem, $totalQty, $lotsSummary, $initialStateCode, $request, $resolvedItems, $workflow) {
            $sm = StockMovement::create([
                'organization_id' => $orgId,
                'reference_code' => $referenceCode,
                'type' => 'outbound',
                'warehouse_id' => $warehouse->id,
                'inventory_item_id' => $firstItem['model']->id,
                'quantity' => $totalQty,
                'item_lot_number' => $lotsSummary,
                'source_system' => 'workshop_api',
                'current_state' => $initialStateCode,
                'workflow_definition_id' => $workflow?->id,
                'created_by' => null, // Created via API
                'notes' => 'API Request from Workshop Management System: ' . ($request->input('notes') ?? 'Standard item issue request.'),
            ]);

            foreach ($resolvedItems as $rItem) {
                StockMovementItem::create([
                    'organization_id' => $orgId,
                    'stock_movement_id' => $sm->id,
                    'inventory_item_id' => $rItem['model']->id,
                    'quantity' => $rItem['quantity'],
                    'item_lot_number' => $rItem['lot_number'],
                ]);
            }

            return $sm;
        });

        // Send notification to Level 1 approvers (e.g. OC or Org Admins)
        $this->notificationService->sendToRole(
            $orgId,
            'oc',
            "New Workshop API Item Request: {$referenceCode}",
            "Workshop Management System requested " . count($resolvedItems) . " product item(s) total {$totalQty} pcs. Awaiting OC approval.",
            'approval_needed',
            route('stock.show', $movement->id)
        );

        $itemsResponseData = [];
        foreach ($resolvedItems as $r) {
            $itemsResponseData[] = [
                'sku' => $r['model']->sku,
                'name' => $r['model']->name,
                'quantity' => $r['quantity'],
                'lot_number' => $r['lot_number'],
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Item request created successfully and entered approval pipeline.',
            'data' => [
                'id' => $movement->id,
                'reference_code' => $movement->reference_code,
                'total_items_count' => count($resolvedItems),
                'total_quantity' => $totalQty,
                'items' => $itemsResponseData,
                'lots_summary' => $lotsSummary,
                'source_system' => $movement->source_system,
                'current_state' => $movement->current_state,
                'created_at' => $movement->created_at->toIso8601String(),
            ]
        ], 201);
    }
}
