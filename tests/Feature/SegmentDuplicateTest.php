<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Segment;
use App\Models\SegmentRule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SegmentDuplicateTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $project = Project::factory()->create();
        $segment = Segment::factory()->create(['project_id' => $project->id]);

        $response = $this->post(route('projects.segments.duplicate', [$project, $segment]), [
            'name' => 'Duplicated',
            'slug' => 'duplicated-abc12',
        ]);
        $response->assertRedirect(route('login'));
    }

    public function test_user_can_duplicate_a_segment(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $segment = Segment::factory()->create([
            'project_id' => $project->id,
            'name' => 'Original',
            'description' => 'Original description',
            'active' => true,
        ]);

        SegmentRule::create([
            'segment_id' => $segment->id,
            'type' => 'comparison',
            'key' => 'utm_source',
            'operator' => '=',
            'value' => 'google',
            'priority' => 0,
        ]);

        SegmentRule::create([
            'segment_id' => $segment->id,
            'type' => 'visit_count',
            'key' => 'visits',
            'operator' => '>=',
            'value' => '3',
            'priority' => 1,
        ]);

        $this->actingAs($user);

        $response = $this->post(route('projects.segments.duplicate', [$project, $segment]), [
            'name' => 'Duplicated Segment',
            'slug' => 'duplicated-segment-x9k2m',
        ]);

        $newSegment = Segment::where('name', 'Duplicated Segment')->first();
        $this->assertNotNull($newSegment);

        $response->assertRedirect(route('projects.segments.edit', [$project->slug, $newSegment]));

        $this->assertDatabaseHas('segments', [
            'id' => $newSegment->id,
            'project_id' => $project->id,
            'name' => 'Duplicated Segment',
            'slug' => 'duplicated-segment-x9k2m',
            'description' => 'Original description',
            'active' => true,
        ]);

        $this->assertCount(2, $newSegment->rules);

        $this->assertDatabaseHas('segment_rules', [
            'segment_id' => $newSegment->id,
            'type' => 'comparison',
            'key' => 'utm_source',
            'operator' => '=',
            'value' => 'google',
        ]);

        $this->assertDatabaseHas('segment_rules', [
            'segment_id' => $newSegment->id,
            'type' => 'visit_count',
            'key' => 'visits',
            'operator' => '>=',
            'value' => '3',
        ]);
    }

    public function test_user_cannot_duplicate_segment_for_another_users_project(): void
    {
        $user = User::factory()->create();
        $otherProject = Project::factory()->create();
        $segment = Segment::factory()->create(['project_id' => $otherProject->id]);

        $this->actingAs($user);

        $response = $this->post(route('projects.segments.duplicate', [$otherProject, $segment]), [
            'name' => 'Stolen',
            'slug' => 'stolen-abc12',
        ]);

        $response->assertForbidden();
    }

    public function test_user_cannot_duplicate_segment_from_different_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $otherProject = Project::factory()->create(['user_id' => $user->id]);
        $segment = Segment::factory()->create(['project_id' => $otherProject->id]);

        $this->actingAs($user);

        $response = $this->post(route('projects.segments.duplicate', [$project, $segment]), [
            'name' => 'Nope',
            'slug' => 'nope-abc12',
        ]);

        $response->assertNotFound();
    }

    public function test_name_is_required(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $segment = Segment::factory()->create(['project_id' => $project->id]);

        $this->actingAs($user);

        $response = $this->post(route('projects.segments.duplicate', [$project, $segment]), [
            'name' => '',
            'slug' => 'some-slug',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_slug_is_required(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $segment = Segment::factory()->create(['project_id' => $project->id]);

        $this->actingAs($user);

        $response = $this->post(route('projects.segments.duplicate', [$project, $segment]), [
            'name' => 'Some Name',
            'slug' => '',
        ]);

        $response->assertSessionHasErrors('slug');
    }

    public function test_slug_must_be_unique(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $segment = Segment::factory()->create([
            'project_id' => $project->id,
            'slug' => 'existing-slug',
        ]);

        $this->actingAs($user);

        $response = $this->post(route('projects.segments.duplicate', [$project, $segment]), [
            'name' => 'New Name',
            'slug' => 'existing-slug',
        ]);

        $response->assertSessionHasErrors('slug');
    }

    public function test_duplicate_segment_without_rules(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $segment = Segment::factory()->create([
            'project_id' => $project->id,
            'name' => 'No Rules',
        ]);

        $this->actingAs($user);

        $response = $this->post(route('projects.segments.duplicate', [$project, $segment]), [
            'name' => 'No Rules Copy',
            'slug' => 'no-rules-copy-ab12c',
        ]);

        $newSegment = Segment::where('name', 'No Rules Copy')->first();
        $this->assertNotNull($newSegment);
        $this->assertEquals('no-rules-copy-ab12c', $newSegment->slug);
        $this->assertCount(0, $newSegment->rules);
    }
}
