<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Segment;
use App\Models\User;
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
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        Segment::factory()->count(3)->create(['project_id' => $project->id]);

        $this->actingAs($user);

        $response = $this->get(route('projects.segments.index', $project));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Segments/Index')
            ->where('project.id', $project->id)
            ->has('segments', 3)
        );
    }

    public function test_user_cannot_view_segments_for_another_users_project(): void
    {
        $user = User::factory()->create();
        $otherProject = Project::factory()->create();

        $this->actingAs($user);

        $response = $this->get(route('projects.segments.index', $otherProject));
        $response->assertForbidden();
    }

    public function test_empty_state_when_project_has_no_segments(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        $response = $this->get(route('projects.segments.index', $project));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Segments/Index')
            ->has('segments', 0)
        );
    }
}
