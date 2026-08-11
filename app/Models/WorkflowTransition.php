<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowTransition extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_definition_id',
        'from_state_id',
        'to_state_id',
        'action_name',
        'allowed_roles',
        'requires_note',
    ];

    protected $casts = [
        'allowed_roles' => 'array',
        'requires_note' => 'boolean',
    ];

    public function definition(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class, 'workflow_definition_id');
    }

    public function fromState(): BelongsTo
    {
        return $this->belongsTo(WorkflowState::class, 'from_state_id');
    }

    public function toState(): BelongsTo
    {
        return $this->belongsTo(WorkflowState::class, 'to_state_id');
    }

    public function isUserAllowed(User $user): bool
    {
        if ($user->is_super_admin || $user->is_org_admin) {
            return true;
        }

        if (empty($this->allowed_roles)) {
            return true; // if no specific role specified, any org user can trigger
        }

        $userRoleSlugs = $user->roles->pluck('slug')->toArray();
        return count(array_intersect($this->allowed_roles, $userRoleSlugs)) > 0;
    }
}
