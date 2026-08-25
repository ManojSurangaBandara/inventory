<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowState;
use App\Services\NotificationService;
use App\Services\WorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowLocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_workflow_definition_and_states_can_define_location(): void
    {
        $org = Organization::create(['name' => 'Apex Logistics', 'code' => 'APEX', 'status' => 'active']);
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@apexlogistics.com',
            'password' => bcrypt('password'),
            'organization_id' => $org->id,
            'is_org_admin' => true,
        ]);

        $wh = Warehouse::create([
            'organization_id' => $org->id,
            'name' => 'Main Depot Colombo',
            'code' => 'DEPOT-CMB',
            'location' => 'Zone 4 Logistics Center',
        ]);

        $this->actingAs($admin);

        // 1. Create a workflow scoped to a warehouse location
        $res = $this->post(route('workflows.store'), [
            'name' => 'Colombo Inbound Lot Workflow',
            'entity_type' => 'StockReceipt',
            'warehouse_id' => $wh->id,
            'description' => 'Inbound lot receiving pipeline for Colombo depot.',
        ]);

        $wf = WorkflowDefinition::where('name', 'Colombo Inbound Lot Workflow')->first();
        $this->assertNotNull($wf);
        $this->assertEquals($wh->id, $wf->warehouse_id);

        // 2. Add workflow state step with physical stage location
        $stateRes = $this->post(route('workflows.states.store', $wf->id), [
            'name' => 'Inspection & Receiving Dock',
            'color' => 'amber',
            'location' => 'Gate 2 Inbound Receiving Bay',
            'is_initial' => true,
        ]);
        $stateRes->assertRedirect(route('workflows.builder', $wf->id));

        $state = WorkflowState::where('workflow_definition_id', $wf->id)->first();
        $this->assertNotNull($state);
        $this->assertEquals('Gate 2 Inbound Receiving Bay', $state->location);

        // 3. Update state step location
        $updateStateRes = $this->put(route('workflows.states.update', $state->id), [
            'name' => 'Inspection & QC Bay',
            'color' => 'purple',
            'location' => 'QC Testing Lab Room 102',
            'is_initial' => true,
        ]);
        $this->assertEquals('QC Testing Lab Room 102', $state->fresh()->location);

        // 4. Update workflow definition location
        $updateWfRes = $this->put(route('workflows.update', $wf->id), [
            'name' => 'Updated Colombo Inbound Pipeline',
            'warehouse_id' => $wh->id,
            'description' => 'Updated description.',
        ]);
        $this->assertEquals('Updated Colombo Inbound Pipeline', $wf->fresh()->name);

        // 5. Test WorkflowService prioritizes warehouse location matching
        $workflowService = app(WorkflowService::class);
        $matchedWf = $workflowService->getActiveWorkflowForType('inbound', $org->id, $wh->id);
        $this->assertNotNull($matchedWf);
        $this->assertEquals($wf->id, $matchedWf->id);
    }
}
