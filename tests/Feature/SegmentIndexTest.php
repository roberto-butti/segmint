<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Segment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SegmentIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $project = Project::factory()->create();

        $response = $this->get(route('projects.segments.index', $project));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_segments_for_their_project(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);

        Segment::factory()->count(3)->create(['project_id' => $project->id]);

        $this->actingAs($user);

        $response = $this->get(route('projects.segments.index', $project));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Segments/Index')
            ->where('project.id', $project->id)
            ->where('canManageProject', true)
            ->has('segments', 3)
        );
    }

    public function test_user_cannot_view_segments_for_another_users_project(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $otherProject = Project::factory()->create();

        $this->actingAs($user);

        $response = $this->get(route('projects.segments.index', $otherProject));
        $response->assertForbidden();
    }

    public function test_empty_state_when_project_has_no_segments(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($user);

        $response = $this->get(route('projects.segments.index', $project));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Segments/Index')
            ->has('segments', 0)
        );
    }

    public function test_index_only_lists_other_projects_the_user_can_manage_as_copy_destinations(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $source = Project::factory()->create(['organization_id' => $organization->id]);
        $manageable = Project::factory()->create(['organization_id' => $organization->id]);
        $viewerOrganization = Organization::factory()->create();
        $viewerOrganization->members()->attach($user, ['role' => OrganizationRole::Viewer->value]);
        Project::factory()->create(['organization_id' => $viewerOrganization->id]);

        $response = $this->actingAs($user)->get(route('projects.segments.index', $source));

        $response->assertInertia(fn ($page) => $page
            ->has('destinationProjects', 1)
            ->where('destinationProjects.0.id', $manageable->id)
        );
    }

    public function test_viewer_cannot_manage_segments_from_the_index(): void
    {
        ['user' => $user] = $this->createUserWithOrganization();
        $organization = Organization::factory()->create();
        $organization->members()->attach($user, ['role' => OrganizationRole::Viewer->value]);
        $project = Project::factory()->create(['organization_id' => $organization->id]);

        $response = $this->actingAs($user)->get(route('projects.segments.index', $project));

        $response->assertInertia(fn ($page) => $page
            ->where('canManageProject', false)
            ->has('destinationProjects', 0)
        );
    }
}
