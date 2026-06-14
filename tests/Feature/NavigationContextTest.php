<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_navigation_context_lists_only_accessible_organizations(): void
    {
        ['user' => $user, 'organization' => $ownedOrganization] = $this->createUserWithOrganization();
        $memberOrganization = Organization::factory()->create();
        $memberOrganization->members()->attach($user, ['role' => OrganizationRole::Member->value]);
        Organization::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('navigationContext.organizations', 2)
                ->where('navigationContext.organization', null)
                ->where('navigationContext.project', null)
                ->has('navigationContext.projects', 0)
            );
    }

    public function test_organization_navigation_context_contains_only_that_organizations_projects(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        Project::factory()->count(2)->create(['organization_id' => $organization->id]);
        Project::factory()->create();

        $this->actingAs($user)
            ->get(route('organizations.dashboard', $organization))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('navigationContext.organization.id', $organization->id)
                ->where('navigationContext.project', null)
                ->has('navigationContext.projects', 2)
            );
    }

    public function test_project_navigation_context_contains_the_current_project_and_organization(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($user)
            ->get(route('projects.segments.index', $project))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('navigationContext.organization.id', $organization->id)
                ->where('navigationContext.project.id', $project->id)
                ->where('navigationContext.project.public_id', $project->public_id)
            );
    }

    public function test_project_navigation_context_lists_personal_favorites_first(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $alpha = Project::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Alpha',
        ]);
        $beta = Project::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Beta',
        ]);
        $favorite = Project::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Zulu favorite',
        ]);
        $user->favoriteProjects()->attach($favorite);

        $this->actingAs($user)
            ->get(route('organizations.dashboard', $organization))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('navigationContext.projects.0.id', $favorite->id)
                ->where('navigationContext.projects.0.is_favorite', true)
                ->where('navigationContext.projects.1.id', $alpha->id)
                ->where('navigationContext.projects.1.is_favorite', false)
                ->where('navigationContext.projects.2.id', $beta->id)
                ->where('navigationContext.projects.2.is_favorite', false)
            );
    }
}
