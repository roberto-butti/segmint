<?php

namespace Tests\Feature;

use App\Models\RuleTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('projects.create'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_create_form(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->get(route('projects.create'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Projects/Create')
        );
    }

    public function test_user_can_create_a_project(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->post(route('projects.store'), [
            'name' => 'My New Project',
            'description' => 'A test project',
        ]);

        $this->assertDatabaseHas('projects', [
            'user_id' => $user->id,
            'name' => 'My New Project',
            'slug' => 'my-new-project',
            'description' => 'A test project',
            'active' => true,
        ]);

        $project = $user->projects()->where('name', 'My New Project')->first();
        $response->assertRedirect(route('projects.show', $project));
    }

    public function test_project_creation_auto_creates_rule_templates(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->post(route('projects.store'), [
            'name' => 'Template Test Project',
        ]);

        $project = $user->projects()->where('name', 'Template Test Project')->first();

        $this->assertCount(count(RuleTemplate::defaults()), $project->ruleTemplates);
    }

    public function test_project_creation_auto_creates_access_token(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->post(route('projects.store'), [
            'name' => 'Token Test Project',
        ]);

        $project = $user->projects()->where('name', 'Token Test Project')->first();

        $this->assertCount(1, $project->accessTokens);
        $this->assertDatabaseHas('access_tokens', [
            'project_id' => $project->id,
            'name' => 'Default',
            'active' => true,
        ]);
    }

    public function test_name_is_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->post(route('projects.store'), [
            'name' => '',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_project_can_be_created_without_description(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->post(route('projects.store'), [
            'name' => 'No Description Project',
        ]);

        $project = $user->projects()->where('name', 'No Description Project')->first();
        $this->assertNotNull($project);
        $this->assertNull($project->description);

        $response->assertRedirect(route('projects.show', $project));
    }
}
