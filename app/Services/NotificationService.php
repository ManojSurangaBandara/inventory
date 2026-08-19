<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\StockMovement;
use App\Models\User;

class NotificationService
{
    /**
     * Send notification to a specific user.
     */
    public function sendToUser(int $organizationId, int $userId, string $title, string $message, string $type = 'info', ?string $linkUrl = null): Notification
    {
        return Notification::create([
            'organization_id' => $organizationId,
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'link_url' => $linkUrl,
            'is_read' => false,
        ]);
    }

    /**
     * Send notification to all users matching a role slug within an organization.
     */
    public function sendToRole(int $organizationId, string $roleSlug, string $title, string $message, string $type = 'info', ?string $linkUrl = null): void
    {
        // Save role notification
        Notification::create([
            'organization_id' => $organizationId,
            'role_slug' => $roleSlug,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'link_url' => $linkUrl,
            'is_read' => false,
        ]);

        // Also notify direct Users with that role
        $users = User::where('organization_id', $organizationId)->get();
        foreach ($users as $u) {
            if ($u->hasRole($roleSlug)) {
                Notification::create([
                    'organization_id' => $organizationId,
                    'user_id' => $u->id,
                    'title' => $title,
                    'message' => $message,
                    'type' => $type,
                    'link_url' => $linkUrl,
                    'is_read' => false,
                ]);
            }
        }
    }

    /**
     * Notify creator and stakeholders of a request rejection.
     */
    public function notifyRejection(StockMovement $movement, string $rejectedByRole, string $reason): void
    {
        $title = "Request Rejected: {$movement->reference_code}";
        $message = "Request {$movement->reference_code} (Lot: {$movement->item_lot_number}) was REJECTED during {$rejectedByRole} inspection. Reason: {$reason}";
        $link = route('stock.show', $movement->id);

        if ($movement->created_by) {
            $this->sendToUser($movement->organization_id, $movement->created_by, $title, $message, 'rejected', $link);
        }

        // Notify tenant admins
        $admins = User::where('organization_id', $movement->organization_id)->where('is_org_admin', true)->get();
        foreach ($admins as $admin) {
            if ($admin->id !== $movement->created_by) {
                $this->sendToUser($movement->organization_id, $admin->id, $title, $message, 'rejected', $link);
            }
        }
    }

    /**
     * Notify approvers at the next workflow level.
     */
    public function notifyNextApprovers(StockMovement $movement, string $nextStateName, array $allowedRoles): void
    {
        $title = "Approval Required: {$movement->reference_code}";
        $message = "Request {$movement->reference_code} (Lot: {$movement->item_lot_number}) is awaiting approval for step: '{$nextStateName}'.";
        $link = route('stock.show', $movement->id);

        if (empty($allowedRoles)) {
            // Notify all tenant admins if no specific roles restriction
            $admins = User::where('organization_id', $movement->organization_id)->where('is_org_admin', true)->get();
            foreach ($admins as $admin) {
                $this->sendToUser($movement->organization_id, $admin->id, $title, $message, 'approval_needed', $link);
            }
        } else {
            foreach ($allowedRoles as $roleSlug) {
                $this->sendToRole($movement->organization_id, $roleSlug, $title, $message, 'approval_needed', $link);
            }
        }
    }

    /**
     * Notify creator and storemen of final approval and fulfillment.
     */
    public function notifyCompletion(StockMovement $movement, string $actionType): void
    {
        $actionLabel = ($actionType === 'inbound') ? 'Stock Added to Main Stock' : 'Stock Issued';
        $title = "Request Completed: {$movement->reference_code}";
        $message = "Request {$movement->reference_code} (Lot: {$movement->item_lot_number}) was APPROVED. Result: {$actionLabel}.";
        $link = route('stock.show', $movement->id);

        if ($movement->created_by) {
            $this->sendToUser($movement->organization_id, $movement->created_by, $title, $message, 'completed', $link);
        }

        $this->sendToRole($movement->organization_id, 'storemen', $title, $message, 'completed', $link);
    }
}
