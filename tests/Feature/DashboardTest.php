<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Models\EventLog;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Segment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_global_dashboard_lists_only_accessible_organizations_with_context(): void
    {
        ['user' => $user, 'organization' => $ownedOrganization] = $this->createUserWithOrganization();
        $ownedOrganization->update(['name' => 'Owned workspace']);
        Project::factory()->count(2)->create(['organization_id' => $ownedOrganization->id]);

        $memberOrganization = Organization::factory()->create(['name' => 'Member workspace']);
        $memberOrganization->members()->attach($user, ['role' => OrganizationRole::Member->value]);
        $assignedProject = Project::factory()->create(['organization_id' => $memberOrganization->id, 'active' => false]);
        $user->assignedProjects()->attach($assignedProject);

        Project::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard')
                ->has('organizations', 2)
                ->where('organizations.0.name', 'Owned workspace')
                ->where('organizations.0.role', 'owner')
                ->where('organizations.0.projects_count', 2)
                ->where('organizations.0.members_count', 1)
                ->where('organizations.1.name', 'Member workspace')
                ->where('organizations.1.role', 'member')
                ->where('organizations.1.projects_count', 1)
                ->where('organizations.1.active_projects_count', 0)
            );
    }

    public function test_member_can_view_an_organization_dashboard_with_scoped_metrics(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);
        $inactiveProject = Project::factory()->create([
            'organization_id' => $organization->id,
            'active' => false,
        ]);
        Segment::factory()->create(['project_id' => $project->id]);
        Segment::factory()->create(['project_id' => $inactiveProject->id, 'active' => false]);
        EventLog::create(['project_id' => $project->id, 'visitor_id' => 'visitor-1', 'event_type' => 'page_view']);
        EventLog::create(['project_id' => $project->id, 'visitor_id' => 'visitor-1', 'event_type' => 'click']);
        EventLog::create(['project_id' => $inactiveProject->id, 'visitor_id' => 'visitor-2', 'event_type' => 'page_view']);

        $otherProject = Project::factory()->create();
        Segment::factory()->create(['project_id' => $otherProject->id]);
        EventLog::create(['project_id' => $otherProject->id, 'visitor_id' => 'outside']);

        $this->actingAs($user)
            ->get(route('organizations.dashboard', $organization))
            ->assertOk()
            ->assertSessionHas('projects_organization_id', $organization->id)
            ->assertInertia(fn ($page) => $page
                ->component('Organizations/Dashboard')
                ->where('organization.public_id', $organization->public_id)
                ->where('currentUserRole.label', 'Owner')
                ->where('canManageProjects', true)
                ->where('stats.projects_count', 2)
                ->where('stats.active_projects_count', 1)
                ->where('stats.segments_count', 2)
                ->where('stats.active_segments_count', 1)
                ->where('stats.events_count', 3)
                ->where('stats.unique_visitors_count', 2)
                ->has('projects', 2)
            );
    }

    public function test_guest_sees_limited_organization_dashboard_without_unassigned_projects(): void
    {
        $organization = Organization::factory()->create();
        $viewer = User::factory()->create();
        $organization->members()->attach($viewer, ['role' => OrganizationRole::Guest->value]);
        Project::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($viewer)
            ->get(route('organizations.dashboard', $organization))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Organizations/Dashboard')
                ->where('currentUserRole.label', 'Guest')
                ->where('limitedProjectView', true)
                ->where('stats.projects_count', 0)
                ->has('projects', 0)
            );
    }

    public function test_user_cannot_view_an_unrelated_organization_dashboard(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $this->actingAs($user)
            ->get(route('organizations.dashboard', $organization))
            ->assertForbidden();
    }
}
