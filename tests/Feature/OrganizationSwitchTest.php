<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationSwitchTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('organizations.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_organizations(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();

        $this->actingAs($user);

        $response = $this->get(route('organizations.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Organizations/Index')
            ->has('organizations', 1)
            ->where('currentOrganizationId', $organization->id)
        );
    }

    public function test_user_sees_all_their_organizations(): void
    {
        ['user' => $user, 'organization' => $org1] = $this->createUserWithOrganization();

        $org2 = Organization::factory()->create();
        $org2->members()->attach($user, ['role' => OrganizationRole::Member->value]);

        $this->actingAs($user);

        $response = $this->get(route('organizations.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Organizations/Index')
            ->has('organizations', 2)
        );
    }

    public function test_user_can_switch_organization(): void
    {
        ['user' => $user, 'organization' => $org1] = $this->createUserWithOrganization();

        $org2 = Organization::factory()->create();
        $org2->members()->attach($user, ['role' => OrganizationRole::Admin->value]);

        Project::factory()->count(2)->create(['organization_id' => $org1->id]);
        Project::factory()->count(5)->create(['organization_id' => $org2->id]);

        $this->actingAs($user);

        // Initially sees org1 projects
        $response = $this->get(route('dashboard'));
        $response->assertInertia(fn ($page) => $page->where('projectsCount', 2));

        // Switch to org2
        $this->post(route('organizations.switch', $org2))->assertRedirect(route('dashboard'));

        // Now sees org2 projects
        $response = $this->get(route('dashboard'));
        $response->assertInertia(fn ($page) => $page->where('projectsCount', 5));
    }

    public function test_user_cannot_switch_to_organization_they_dont_belong_to(): void
    {
        ['user' => $user, 'organization' => $org] = $this->createUserWithOrganization();

        $otherOrg = Organization::factory()->create();

        $this->actingAs($user);

        $this->post(route('organizations.switch', $otherOrg))->assertForbidden();
    }

    public function test_organization_shows_role_and_project_count(): void
    {
        ['user' => $user, 'organization' => $org] = $this->createUserWithOrganization();
        Project::factory()->count(3)->create(['organization_id' => $org->id]);

        $this->actingAs($user);

        $response = $this->get(route('organizations.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Organizations/Index')
            ->where('organizations.0.role', 'owner')
            ->where('organizations.0.projects_count', 3)
        );
    }
}
