<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowState;
use App\Models\WorkflowTransition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class WorkflowController extends Controller
{
    public function index()
    {
        $workflows = WorkflowDefinition::where('organization_id', Auth::user()->organization_id)
            ->withCount(['states', 'transitions'])
            ->get();

        return view('workflows.index', compact('workflows'));
    }

    public function storeDefinition(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'entity_type' => 'required|string|in:StockMovement,PurchaseOrder,InventoryItem',
            'description' => 'nullable|string',
        ]);

        $workflow = WorkflowDefinition::create([
            'organization_id' => Auth::user()->organization_id,
            'name' => $request->name,
            'entity_type' => $request->entity_type,
            'description' => $request->description,
            'is_active' => true,
        ]);

        // Auto-create default initial 'Draft' state and final 'Completed' state
        $draftState = WorkflowState::create([
            'workflow_definition_id' => $workflow->id,
            'code' => 'draft',
            'name' => 'Draft',
            'color' => 'slate',
            'is_initial' => true,
            'is_final' => false,
        ]);

        $completedState = WorkflowState::create([
            'workflow_definition_id' => $workflow->id,
            'code' => 'completed',
            'name' => 'Completed',
            'color' => 'emerald',
            'is_initial' => false,
            'is_final' => true,
        ]);

        WorkflowTransition::create([
            'workflow_definition_id' => $workflow->id,
            'from_state_id' => $draftState->id,
            'to_state_id' => $completedState->id,
            'action_name' => 'Complete Process',
            'allowed_roles' => [],
            'requires_note' => false,
        ]);

        return redirect()->route('workflows.builder', $workflow->id)->with('success', "Workflow '{$workflow->name}' created.");
    }

    public function builder(int $id)
    {
        $workflow = WorkflowDefinition::where('organization_id', Auth::user()->organization_id)
            ->with(['states.outgoingTransitions.toState', 'transitions.fromState', 'transitions.toState'])
            ->findOrFail($id);

        $roles = Role::where('organization_id', Auth::user()->organization_id)->get();

        return view('workflows.builder', compact('workflow', 'roles'));
    }

    public function storeState(Request $request, int $workflowId)
    {
        $workflow = WorkflowDefinition::where('organization_id', Auth::user()->organization_id)->findOrFail($workflowId);

        $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'required|string',
            'is_initial' => 'nullable|boolean',
            'is_final' => 'nullable|boolean',
        ]);

        $code = Str::slug($request->name, '_');

        if ($request->boolean('is_initial')) {
            // Unset initial flag on other states for this workflow
            WorkflowState::where('workflow_definition_id', $workflow->id)->update(['is_initial' => false]);
        }

        WorkflowState::create([
            'workflow_definition_id' => $workflow->id,
            'code' => $code,
            'name' => $request->name,
            'color' => $request->color,
            'is_initial' => $request->boolean('is_initial'),
            'is_final' => $request->boolean('is_final'),
        ]);

        return redirect()->route('workflows.builder', $workflow->id)->with('success', "Workflow State '{$request->name}' added.");
    }

    public function deleteState(int $id)
    {
        $state = WorkflowState::findOrFail($id);
        $workflowId = $state->workflow_definition_id;
        $state->delete();

        return redirect()->route('workflows.builder', $workflowId)->with('success', 'Workflow State deleted.');
    }

    public function storeTransition(Request $request, int $workflowId)
    {
        $workflow = WorkflowDefinition::where('organization_id', Auth::user()->organization_id)->findOrFail($workflowId);

        $request->validate([
            'from_state_id' => 'required|exists:workflow_states,id',
            'to_state_id' => 'required|exists:workflow_states,id|different:from_state_id',
            'action_name' => 'required|string|max:255',
            'allowed_roles' => 'nullable|array',
            'requires_note' => 'nullable|boolean',
        ]);

        WorkflowTransition::create([
            'workflow_definition_id' => $workflow->id,
            'from_state_id' => $request->from_state_id,
            'to_state_id' => $request->to_state_id,
            'action_name' => $request->action_name,
            'allowed_roles' => $request->allowed_roles ?? [],
            'requires_note' => $request->boolean('requires_note'),
        ]);

        return redirect()->route('workflows.builder', $workflow->id)->with('success', "Transition '{$request->action_name}' created.");
    }

    public function deleteTransition(int $id)
    {
        $transition = WorkflowTransition::findOrFail($id);
        $workflowId = $transition->workflow_definition_id;
        $transition->delete();

        return redirect()->route('workflows.builder', $workflowId)->with('success', 'Transition removed.');
    }
}
