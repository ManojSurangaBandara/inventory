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

class WorkflowService
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get active workflow definition for entity type.
     */
    public function getActiveWorkflow(string $entityType, ?int $organizationId = null): ?WorkflowDefinition
    {
        $query = WorkflowDefinition::where('entity_type', $entityType)->where('is_active', true);
        if ($organizationId) {
            $query->where('organization_id', $organizationId);
        }
        return $query->with(['states', 'transitions.fromState', 'transitions.toState'])->first();
    }

    /**
     * Get available transitions for an entity in its current state.
     */
    public function getAvailableTransitions(Model $entity, User $user): array
    {
        $entityType = class_basename($entity);
        $workflow = $this->getActiveWorkflow($entityType, $entity->organization_id);

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

            if ($entity instanceof StockMovement && (str_contains(strtolower($toStateCode), 'reject') || $toStateCode === 'rejected')) {
                $entity->rejection_reason = $notes;
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
            $item = InventoryItem::find($movement->inventory_item_id);
            if ($item) {
                if ($movement->type === 'inbound') {
                    $item->increment('current_stock', $movement->quantity);
                } elseif ($movement->type === 'outbound') {
                    $item->decrement('current_stock', min($item->current_stock, $movement->quantity));
                } elseif ($movement->type === 'adjustment') {
                    $item->current_stock = max(0, $movement->quantity);
                    $item->save();
                }
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
}
