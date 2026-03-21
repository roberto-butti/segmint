<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Segment;
use App\Models\SegmentRule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SegmentDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $project = Project::factory()->create();
        $segment = Segment::factory()->create(['project_id' => $project->id]);

        $response = $this->delete(route('projects.segments.destroy', [$project, $segment]));
        $response->assertRedirect(route('login'));
    }

    public function test_user_can_delete_a_segment(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $segment = Segment::factory()->create(['project_id' => $project->id]);

        SegmentRule::create([
            'segment_id' => $segment->id,
            'type' => 'comparison',
            'key' => 'utm_source',
            'operator' => '=',
            'value' => 'google',
            'priority' => 0,
        ]);

        $this->actingAs($user);

        $response = $this->delete(route('projects.segments.destroy', [$project, $segment]));

        $response->assertRedirect(route('projects.segments.index', $project->slug));
        $this->assertDatabaseMissing('segments', ['id' => $segment->id]);
        $this->assertDatabaseMissing('segment_rules', ['segment_id' => $segment->id]);
    }

    public function test_user_cannot_delete_segment_for_another_users_project(): void
    {
        $user = User::factory()->create();
        $otherProject = Project::factory()->create();
        $segment = Segment::factory()->create(['project_id' => $otherProject->id]);

        $this->actingAs($user);

        $response = $this->delete(route('projects.segments.destroy', [$otherProject, $segment]));
        $response->assertForbidden();

        $this->assertDatabaseHas('segments', ['id' => $segment->id]);
    }

    public function test_user_cannot_delete_segment_from_different_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $otherProject = Project::factory()->create(['user_id' => $user->id]);
        $segment = Segment::factory()->create(['project_id' => $otherProject->id]);

        $this->actingAs($user);

        $response = $this->delete(route('projects.segments.destroy', [$project, $segment]));
        $response->assertNotFound();

        $this->assertDatabaseHas('segments', ['id' => $segment->id]);
    }

    public function test_deleting_segment_does_not_affect_other_segments(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $segmentToDelete = Segment::factory()->create(['project_id' => $project->id]);
        $segmentToKeep = Segment::factory()->create(['project_id' => $project->id]);

        $this->actingAs($user);

        $this->delete(route('projects.segments.destroy', [$project, $segmentToDelete]));

        $this->assertDatabaseMissing('segments', ['id' => $segmentToDelete->id]);
        $this->assertDatabaseHas('segments', ['id' => $segmentToKeep->id]);
    }
}
