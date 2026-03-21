<?php

namespace Tests\Feature;

use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $project = Project::factory()->create();

        $this->get(route('projects.edit', $project))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_edit_form(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($user);

        $response = $this->get(route('projects.edit', $project));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Projects/Edit')
            ->where('project.id', $project->id)
        );
    }

    public function test_user_cannot_view_edit_form_for_another_users_project(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $otherProject = Project::factory()->create();

        $this->actingAs($user);

        $this->get(route('projects.edit', $otherProject))->assertForbidden();
    }

    public function test_authenticated_user_can_update_their_project(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id, 'active' => true]);

        $this->actingAs($user);

        $response = $this->put(route('projects.update', $project), [
            'name' => 'Updated Project Name',
            'description' => 'Updated description',
            'active' => false,
        ]);

        $response->assertRedirect(route('projects.show', $project));

        $project->refresh();
        $this->assertEquals('Updated Project Name', $project->name);
        $this->assertEquals('Updated description', $project->description);
        $this->assertFalse($project->active);
    }

    public function test_user_cannot_update_another_users_project(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $otherProject = Project::factory()->create();

        $this->actingAs($user);

        $this->put(route('projects.update', $otherProject), [
            'name' => 'Hacked',
            'description' => 'Hacked',
            'active' => false,
        ])->assertForbidden();
    }

    public function test_name_is_required(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($user);

        $response = $this->put(route('projects.update', $project), [
            'name' => '',
            'active' => true,
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_active_is_required(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($user);

        $response = $this->put(route('projects.update', $project), [
            'name' => 'Test',
        ]);

        $response->assertSessionHasErrors('active');
    }
}
