<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowState extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_definition_id',
        'code',
        'name',
        'color',
        'is_initial',
        'is_final',
    ];

    protected $casts = [
        'is_initial' => 'boolean',
        'is_final' => 'boolean',
    ];

    public function definition(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class, 'workflow_definition_id');
    }

    public function outgoingTransitions(): HasMany
    {
        return $this->hasMany(WorkflowTransition::class, 'from_state_id');
    }

    public function incomingTransitions(): HasMany
    {
        return $this->hasMany(WorkflowTransition::class, 'to_state_id');
    }
}
