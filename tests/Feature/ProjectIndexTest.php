<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ProjectIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $response = $this->get(route('projects.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_sees_owned_org_projects_by_default(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        Project::factory()->count(3)->create(['organization_id' => $organization->id]);

        $this->actingAs($user);

        $response = $this->get(route('projects.index'));

        $response->assertRedirect(route('organizations.projects.index', $organization));

        $response = $this->get(route('organizations.projects.index', $organization));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Projects/Index')
            ->where('organization.id', $organization->id)
            ->where('canManageProjects', true)
            ->has('projects', 3)
        );
    }

    public function test_user_can_filter_by_organization(): void
    {
        ['user' => $user, 'organization' => $org1] = $this->createUserWithOrganization();

        $org2 = Organization::factory()->create();
        $org2->members()->attach($user, ['role' => OrganizationRole::Member->value]);

        Project::factory()->count(2)->create(['organization_id' => $org1->id]);
        Project::factory()->count(4)->create(['organization_id' => $org2->id]);

        $this->actingAs($user);

        $response = $this->get(route('organizations.projects.index', $org2));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('organization.id', $org2->id)
            ->has('projects', 4)
        );
    }

    public function test_project_collection_marks_only_the_current_users_favorites(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $favorite = Project::factory()->create(['organization_id' => $organization->id]);
        $other = Project::factory()->create(['organization_id' => $organization->id]);
        $user->favoriteProjects()->attach($favorite);

        $response = $this->actingAs($user)
            ->get(route('organizations.projects.index', $organization));

        $response->assertInertia(fn ($page) => $page
            ->has('projects', 2)
            ->where('projects', fn (Collection $projects) => $projects->firstWhere('id', $favorite->id)['is_favorite'] === true
                && $projects->firstWhere('id', $other->id)['is_favorite'] === false)
        );
    }

    public function test_selected_org_persists_in_session(): void
    {
        ['user' => $user, 'organization' => $org1] = $this->createUserWithOrganization();

        $org2 = Organization::factory()->create();
        $org2->members()->attach($user, ['role' => OrganizationRole::Member->value]);

        Project::factory()->count(1)->create(['organization_id' => $org2->id]);

        $this->actingAs($user);

        $this->get(route('organizations.projects.index', $org2));

        $response = $this->get(route('projects.index'));

        $response->assertRedirect(route('organizations.projects.index', $org2));
    }

    public function test_user_without_owned_org_is_redirected_to_first_accessible_organization(): void
    {
        // Create a user that does NOT own any org
        $user = User::factory()->create(['owned_organization_id' => null]);

        $viewerOrg = Organization::factory()->create();
        $viewerOrg->members()->attach($user, ['role' => OrganizationRole::Viewer->value]);

        $this->actingAs($user);

        $response = $this->get(route('projects.index'));

        $response->assertRedirect(route('organizations.projects.index', $viewerOrg));
    }

    public function test_project_collection_does_not_include_other_organization_options(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();

        // Other org the user doesn't belong to
        Organization::factory()->create();

        $this->actingAs($user);

        $response = $this->get(route('organizations.projects.index', $organization));

        $response->assertInertia(fn ($page) => $page
            ->missing('organizations')
            ->where('organization.id', $organization->id)
        );
    }

    public function test_viewer_cannot_create_projects_from_project_collection(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->members()->attach($user, ['role' => OrganizationRole::Viewer->value]);

        $this->actingAs($user)
            ->get(route('organizations.projects.index', $organization))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('canManageProjects', false)
            );
    }

    public function test_user_cannot_view_another_organizations_project_collection(): void
    {
        ['user' => $user] = $this->createUserWithOrganization();
        $otherOrganization = Organization::factory()->create();

        $this->actingAs($user)
            ->get(route('organizations.projects.index', $otherOrganization))
            ->assertForbidden();
    }

    public function test_organization_routes_use_public_ids(): void
    {
        $organization = Organization::factory()->create();

        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{12}$/', $organization->public_id);
        $this->assertStringEndsWith(
            "/organizations/{$organization->public_id}/projects",
            route('organizations.projects.index', $organization),
        );
    }
}
