<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowLog;
use App\Models\WorkflowState;
use App\Models\WorkflowTransition;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

use App\Models\WarehouseStock;

class WorkflowService
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get active workflow definition for entity type, with optional warehouse/location matching.
     */
    public function getActiveWorkflow(string $entityType, ?int $organizationId = null, ?int $warehouseId = null): ?WorkflowDefinition
    {
        // 1. If warehouseId is provided, try to find a warehouse-specific workflow first
        if ($warehouseId) {
            $specificQuery = WorkflowDefinition::where('entity_type', $entityType)
                ->where('warehouse_id', $warehouseId)
                ->where('is_active', true);

            if ($organizationId) {
                $specificQuery->where('organization_id', $organizationId);
            }

            $specificWorkflow = $specificQuery->with(['states', 'transitions.fromState', 'transitions.toState'])->first();
            if ($specificWorkflow) {
                return $specificWorkflow;
            }
        }

        // 2. Fallback to global / general workflow (where warehouse_id is null or any active)
        $query = WorkflowDefinition::where('entity_type', $entityType)->where('is_active', true);
        if ($organizationId) {
            $query->where('organization_id', $organizationId);
        }
        return $query->with(['states', 'transitions.fromState', 'transitions.toState'])->first();
    }

    /**
     * Get active workflow specifically for a stock movement lifecycle type and optional warehouse location.
     * Checks specialized workflow first, falling back to general StockMovement workflow.
     */
    public function getActiveWorkflowForType(string $movementType, ?int $organizationId = null, ?int $warehouseId = null): ?WorkflowDefinition
    {
        $typeMapping = [
            'outbound' => 'StockDispatch',
            'inbound' => 'StockReceipt',
            'transfer' => 'StockTransfer',
            'adjustment' => 'StockAdjustment',
        ];

        $targetEntity = $typeMapping[$movementType] ?? 'StockMovement';

        // 1. Try specialized entity type workflow (with warehouse location priority)
        $workflow = $this->getActiveWorkflow($targetEntity, $organizationId, $warehouseId);
        if ($workflow) {
            return $workflow;
        }

        // 2. Fallback to general StockMovement workflow
        if ($targetEntity !== 'StockMovement') {
            return $this->getActiveWorkflow('StockMovement', $organizationId, $warehouseId);
        }

        return null;
    }

    /**
     * Get the bound or active workflow definition for any entity model.
     */
    public function getWorkflowForEntity(Model $entity): ?WorkflowDefinition
    {
        if ($entity instanceof StockMovement) {
            if (!empty($entity->workflow_definition_id)) {
                $boundWf = WorkflowDefinition::with(['states', 'transitions.fromState', 'transitions.toState'])
                    ->find($entity->workflow_definition_id);
                if ($boundWf) {
                    return $boundWf;
                }
            }

            return $this->getActiveWorkflowForType($entity->type ?? 'inbound', $entity->organization_id, $entity->warehouse_id);
        }

        $entityType = class_basename($entity);
        $warehouseId = $entity->warehouse_id ?? null;
        return $this->getActiveWorkflow($entityType, $entity->organization_id, $warehouseId);
    }

    /**
     * Get available transitions for an entity in its current state.
     */
    public function getAvailableTransitions(Model $entity, User $user): array
    {
        $workflow = $this->getWorkflowForEntity($entity);

        if (!$workflow) {
            return [];
        }

        $currentStateCode = $entity->current_state ?? 'draft';
        $currentState = $workflow->states->firstWhere('code', $currentStateCode);

        if (!$currentState) {
            return [];
        }

        $transitions = WorkflowTransition::where('workflow_definition_id', $workflow->id)
            ->where('from_state_id', $currentState->id)
            ->with(['toState'])
            ->get();

        $available = [];
        foreach ($transitions as $transition) {
            if ($transition->isUserAllowed($user)) {
                $available[] = [
                    'transition_id' => $transition->id,
                    'action_name' => $transition->action_name,
                    'to_state_code' => $transition->toState->code,
                    'to_state_name' => $transition->toState->name,
                    'to_state_color' => $transition->toState->color,
                    'requires_note' => $transition->requires_note,
                ];
            }
        }

        return $available;
    }

    /**
     * Perform workflow state transition on model entity.
     */
    public function executeTransition(Model $entity, int $transitionId, User $user, ?string $notes = null): Model
    {
        $transition = WorkflowTransition::with(['fromState', 'toState', 'definition'])->findOrFail($transitionId);

        if (!$transition->isUserAllowed($user)) {
            throw new Exception("Unauthorized: Your role does not permit executing the action '{$transition->action_name}'.");
        }

        if ($transition->requires_note && empty(trim($notes ?? ''))) {
            throw new Exception("A note/reason is required to perform this action.");
        }

        DB::transaction(function () use ($entity, $transition, $user, $notes) {
            $fromStateCode = $entity->current_state;
            $toStateCode = $transition->toState->code;

            $entity->current_state = $toStateCode;

            if ($entity instanceof StockMovement) {
                if (empty($entity->workflow_definition_id)) {
                    $entity->workflow_definition_id = $transition->workflow_definition_id;
                }
                if (str_contains(strtolower($toStateCode), 'reject') || $toStateCode === 'rejected') {
                    $entity->rejection_reason = $notes;
                }
            }

            $entity->save();

            // Record audit log
            WorkflowLog::create([
                'organization_id' => $entity->organization_id,
                'entity_type' => class_basename($entity),
                'entity_id' => $entity->id,
                'from_state' => $fromStateCode,
                'to_state' => $toStateCode,
                'action' => $transition->action_name,
                'user_id' => $user->id,
                'notes' => $notes,
            ]);

            // Entity specific side-effects & Notifications
            if ($entity instanceof StockMovement) {
                $this->applyStockMovementEffects($entity, $transition->toState, $user, $notes);
            }
        });

        return $entity;
    }

    /**
     * Apply stock adjustments and dispatch notifications when stock movement state changes.
     */
    protected function applyStockMovementEffects(StockMovement $movement, WorkflowState $toState, User $actor, ?string $notes = null): void
    {
        $toCode = strtolower($toState->code);

        // Case 1: Rejection
        if ($toState->is_final && (str_contains($toCode, 'reject') || $toCode === 'rejected')) {
            $actorRoleName = $actor->roles->first()?->name ?? ($actor->isOrgAdmin() ? 'Org Admin' : 'Approver');
            $this->notificationService->notifyRejection($movement, $actorRoleName, $notes ?? 'Information incorrect or rejected.');
            return;
        }

        // Case 2: Final Approval & Fulfillment
        if ($toState->is_final || in_array($toCode, ['completed', 'approved', 'dispatched', 'issued'])) {
            $movement->loadMissing('items.item');

            if ($movement->items->isNotEmpty()) {
                foreach ($movement->items as $movementItem) {
                    $this->adjustItemAndWarehouseStock(
                        $movement->organization_id,
                        $movementItem->inventory_item_id,
                        $movement->warehouse_id,
                        $movement->target_warehouse_id,
                        $movement->type,
                        (float) $movementItem->quantity
                    );
                }
            } elseif ($movement->inventory_item_id) {
                $this->adjustItemAndWarehouseStock(
                    $movement->organization_id,
                    $movement->inventory_item_id,
                    $movement->warehouse_id,
                    $movement->target_warehouse_id,
                    $movement->type,
                    (float) $movement->quantity
                );
            }

            $this->notificationService->notifyCompletion($movement, $movement->type);
            return;
        }

        // Case 3: Advanced to next intermediate approval step
        $nextTransitions = WorkflowTransition::where('workflow_definition_id', $toState->workflow_definition_id)
            ->where('from_state_id', $toState->id)
            ->get();

        $allowedRoles = [];
        foreach ($nextTransitions as $nt) {
            if (is_array($nt->allowed_roles)) {
                $allowedRoles = array_merge($allowedRoles, $nt->allowed_roles);
            }
        }
        $allowedRoles = array_unique($allowedRoles);

        $this->notificationService->notifyNextApprovers($movement, $toState->name, $allowedRoles);
    }

    /**
     * Synchronize stock balance on both master InventoryItem and specific WarehouseStock records.
     */
    protected function adjustItemAndWarehouseStock(
        int $orgId,
        int $itemId,
        ?int $warehouseId,
        ?int $targetWarehouseId,
        string $type,
        float $qty
    ): void {
        $item = InventoryItem::find($itemId);
        if (!$item) {
            return;
        }

        if ($type === 'inbound') {
            $item->increment('current_stock', $qty);

            if ($warehouseId) {
                $whStock = WarehouseStock::firstOrCreate(
                    ['organization_id' => $orgId, 'warehouse_id' => $warehouseId, 'inventory_item_id' => $itemId],
                    ['current_stock' => 0, 'reorder_level' => $item->reorder_level]
                );
                $whStock->increment('current_stock', $qty);
            }
        } elseif ($type === 'outbound') {
            $item->decrement('current_stock', min($item->current_stock, $qty));

            if ($warehouseId) {
                $whStock = WarehouseStock::firstOrCreate(
                    ['organization_id' => $orgId, 'warehouse_id' => $warehouseId, 'inventory_item_id' => $itemId],
                    ['current_stock' => 0, 'reorder_level' => $item->reorder_level]
                );
                $whStock->decrement('current_stock', min($whStock->current_stock, $qty));
            }
        } elseif ($type === 'transfer') {
            // Transfer shifts stock between warehouses without altering organization-wide total
            if ($warehouseId) {
                $originStock = WarehouseStock::firstOrCreate(
                    ['organization_id' => $orgId, 'warehouse_id' => $warehouseId, 'inventory_item_id' => $itemId],
                    ['current_stock' => 0, 'reorder_level' => $item->reorder_level]
                );
                $originStock->decrement('current_stock', min($originStock->current_stock, $qty));
            }

            if ($targetWarehouseId) {
                $targetStock = WarehouseStock::firstOrCreate(
                    ['organization_id' => $orgId, 'warehouse_id' => $targetWarehouseId, 'inventory_item_id' => $itemId],
                    ['current_stock' => 0, 'reorder_level' => $item->reorder_level]
                );
                $targetStock->increment('current_stock', $qty);
            }
        } elseif ($type === 'adjustment') {
            $item->current_stock = max(0, $qty);
            $item->save();

            if ($warehouseId) {
                $whStock = WarehouseStock::firstOrCreate(
                    ['organization_id' => $orgId, 'warehouse_id' => $warehouseId, 'inventory_item_id' => $itemId],
                    ['current_stock' => 0, 'reorder_level' => $item->reorder_level]
                );
                $whStock->current_stock = max(0, $qty);
                $whStock->save();
            }
        }
    }
}
