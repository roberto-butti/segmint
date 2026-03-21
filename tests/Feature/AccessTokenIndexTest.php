<?php

namespace Tests\Feature;

use App\Models\AccessToken;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessTokenIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $project = Project::factory()->create();

        $this->get(route('projects.access-tokens.index', $project))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_access_tokens(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        AccessToken::factory()->count(3)->create(['project_id' => $project->id]);

        $this->actingAs($user);

        $response = $this->get(route('projects.access-tokens.index', $project));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('AccessTokens/Index')
            ->where('project.id', $project->id)
            ->has('accessTokens', 3)
        );
    }

    public function test_user_cannot_view_access_tokens_for_another_users_project(): void
    {
        $user = User::factory()->create();
        $otherProject = Project::factory()->create();

        $this->actingAs($user);

        $this->get(route('projects.access-tokens.index', $otherProject))
            ->assertForbidden();
    }

    public function test_empty_state_when_project_has_no_tokens(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        $response = $this->get(route('projects.access-tokens.index', $project));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('AccessTokens/Index')
            ->has('accessTokens', 0)
        );
    }
}
