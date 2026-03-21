<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Segment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $project = Project::factory()->create();

        $response = $this->get(route('projects.show', $project));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_their_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        Segment::factory()->count(3)->create(['project_id' => $project->id]);
        Segment::factory()->create(['project_id' => $project->id, 'active' => false]);

        $this->actingAs($user);

        $response = $this->get(route('projects.show', $project));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Projects/Show')
            ->where('project.id', $project->id)
            ->where('segmentsCount', 4)
            ->where('activeSegmentsCount', 3)
            ->where('eventLogsCount', 0)
        );
    }

    public function test_user_cannot_view_another_users_project(): void
    {
        $user = User::factory()->create();
        $otherProject = Project::factory()->create();

        $this->actingAs($user);

        $response = $this->get(route('projects.show', $otherProject));
        $response->assertForbidden();
    }
}
