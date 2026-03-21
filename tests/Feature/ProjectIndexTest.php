<?php

namespace Tests\Feature;

use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $response = $this->get(route('projects.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_projects_list(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $projects = Project::factory()->count(3)->create(['organization_id' => $organization->id]);

        $this->actingAs($user);

        $response = $this->get(route('projects.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Projects/Index')
            ->has('projects', 3)
        );
    }

    public function test_user_only_sees_their_own_projects(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();

        Project::factory()->count(2)->create(['organization_id' => $organization->id]);
        Project::factory()->count(3)->create(); // different organization

        $this->actingAs($user);

        $response = $this->get(route('projects.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Projects/Index')
            ->has('projects', 2)
        );
    }

    public function test_empty_state_when_user_has_no_projects(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();

        $this->actingAs($user);

        $response = $this->get(route('projects.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Projects/Index')
            ->has('projects', 0)
        );
    }
}
