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

        return redirect()->route('workflows.builder', $workflow->id)->with('success', "Workflow '{$workflow->name}' created. Add custom state steps to configure the pipeline.");
    }

    public function destroyDefinition(int $id)
    {
        $workflow = WorkflowDefinition::where('organization_id', Auth::user()->organization_id)->findOrFail($id);
        $name = $workflow->name;
        $workflow->delete();

        return redirect()->route('workflows.index')->with('success', "Workflow '{$name}' deleted.");
    }

    public function builder(int $id)
    {
        $workflow = WorkflowDefinition::where('organization_id', Auth::user()->organization_id)
            ->with([
                'states' => function ($q) {
                    $q->orderBy('sort_order', 'asc')->orderBy('id', 'asc');
                },
                'states.outgoingTransitions.toState',
                'transitions.fromState',
                'transitions.toState'
            ])
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

        $maxSort = WorkflowState::where('workflow_definition_id', $workflow->id)->max('sort_order') ?? 0;

        WorkflowState::create([
            'organization_id' => $workflow->organization_id,
            'workflow_definition_id' => $workflow->id,
            'code' => $code,
            'name' => $request->name,
            'color' => $request->color,
            'is_initial' => $request->boolean('is_initial'),
            'is_final' => $request->boolean('is_final'),
            'sort_order' => $maxSort + 1,
        ]);

        return redirect()->route('workflows.builder', $workflow->id)->with('success', "Workflow State '{$request->name}' added.");
    }

    public function updateState(Request $request, int $id)
    {
        $state = WorkflowState::findOrFail($id);
        $workflow = WorkflowDefinition::where('organization_id', Auth::user()->organization_id)->findOrFail($state->workflow_definition_id);

        $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'required|string',
            'is_initial' => 'nullable|boolean',
            'is_final' => 'nullable|boolean',
        ]);

        $code = Str::slug($request->name, '_');

        if ($request->boolean('is_initial')) {
            // Unset initial flag on other states for this workflow
            WorkflowState::where('workflow_definition_id', $workflow->id)
                ->where('id', '!=', $state->id)
                ->update(['is_initial' => false]);
        }

        $state->update([
            'code' => $code,
            'name' => $request->name,
            'color' => $request->color,
            'is_initial' => $request->boolean('is_initial'),
            'is_final' => $request->boolean('is_final'),
        ]);

        return redirect()->route('workflows.builder', $workflow->id)->with('success', "Workflow State '{$request->name}' updated.");
    }

    public function reorderStates(Request $request, int $workflowId)
    {
        $workflow = WorkflowDefinition::where('organization_id', Auth::user()->organization_id)->findOrFail($workflowId);
        $stateIds = $request->input('state_ids', []);

        foreach ($stateIds as $index => $stateId) {
            WorkflowState::where('workflow_definition_id', $workflow->id)
                ->where('id', $stateId)
                ->update(['sort_order' => $index + 1]);
        }

        return response()->json(['success' => true, 'message' => 'States reordered successfully.']);
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
            'organization_id' => $workflow->organization_id,
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
