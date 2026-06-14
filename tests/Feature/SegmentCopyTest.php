<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Models\EventLog;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Segment;
use App\Models\SegmentMatch;
use App\Models\SegmentRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SegmentCopyTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_copy_segments_and_rules_to_another_project(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $source = Project::factory()->create(['organization_id' => $organization->id]);
        $destination = Project::factory()->create(['organization_id' => $organization->id]);
        $segment = Segment::factory()->create([
            'project_id' => $source->id,
            'slug' => 'high-value-visitors',
        ]);
        $rule = SegmentRule::create([
            'segment_id' => $segment->id,
            'type' => 'comparison',
            'key' => 'utm_source',
            'operator' => '=',
            'value' => 'google',
            'priority' => 0,
        ]);
        $eventLog = EventLog::create([
            'project_id' => $source->id,
            'event_type' => 'page_view',
        ]);
        SegmentMatch::create([
            'event_log_id' => $eventLog->id,
            'segment_id' => $segment->id,
            'matched' => true,
        ]);

        $response = $this->actingAs($user)->post(route('projects.segments.copy', $source), [
            'destination_project_id' => $destination->id,
            'segment_ids' => [$segment->id],
        ]);

        $copy = $destination->segments()->where('slug', $segment->slug)->first();

        $this->assertNotNull($copy);
        $this->assertSame($segment->name, $copy->name);
        $this->assertSame($segment->description, $copy->description);
        $this->assertSame($segment->active, $copy->active);
        $this->assertDatabaseHas('segment_rules', [
            'segment_id' => $copy->id,
            'type' => $rule->type,
            'key' => $rule->key,
            'operator' => $rule->operator,
            'value' => $rule->value,
            'priority' => $rule->priority,
        ]);
        $this->assertDatabaseMissing('segment_matches', ['segment_id' => $copy->id]);
        $response->assertRedirect(route('projects.segments.index', $source));
        $response->assertSessionHas('segmentCopy', fn (array $result) => filled($result['id'])
            && $result['message'] === "1 segment copied into {$destination->name}."
            && $result['destination_name'] === $destination->name
            && $result['destination_url'] === route('projects.segments.index', $destination));
    }

    public function test_copy_skips_slugs_that_already_exist_in_destination(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $source = Project::factory()->create(['organization_id' => $organization->id]);
        $destination = Project::factory()->create(['organization_id' => $organization->id]);
        $conflict = Segment::factory()->create(['project_id' => $source->id, 'slug' => 'existing']);
        $copyable = Segment::factory()->create(['project_id' => $source->id, 'slug' => 'new']);
        Segment::factory()->create(['project_id' => $destination->id, 'slug' => 'existing']);

        $response = $this->actingAs($user)->post(route('projects.segments.copy', $source), [
            'destination_project_id' => $destination->id,
            'segment_ids' => [$conflict->id, $copyable->id],
        ]);

        $this->assertCount(1, $destination->segments()->where('slug', 'existing')->get());
        $this->assertTrue($destination->segments()->where('slug', 'new')->exists());
        $response->assertRedirect(route('projects.segments.index', $source));
        $response->assertSessionHas('segmentCopy', fn (array $result) => filled($result['id'])
            && $result['message'] === "1 segment copied into {$destination->name}. 1 segment skipped because the slug already exists."
            && $result['destination_name'] === $destination->name
            && $result['destination_url'] === route('projects.segments.index', $destination));
    }

    public function test_user_cannot_copy_to_a_project_they_cannot_manage(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $source = Project::factory()->create(['organization_id' => $organization->id]);
        $segment = Segment::factory()->create(['project_id' => $source->id]);
        $viewerOrganization = Organization::factory()->create();
        $viewerOrganization->members()->attach($user, ['role' => OrganizationRole::Guest->value]);
        $destination = Project::factory()->create(['organization_id' => $viewerOrganization->id]);

        $response = $this->actingAs($user)->post(route('projects.segments.copy', $source), [
            'destination_project_id' => $destination->id,
            'segment_ids' => [$segment->id],
        ]);

        $response->assertForbidden();
        $this->assertFalse($destination->segments()->where('slug', $segment->slug)->exists());
    }

    public function test_user_cannot_copy_segments_from_another_source_project(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $source = Project::factory()->create(['organization_id' => $organization->id]);
        $otherSource = Project::factory()->create(['organization_id' => $organization->id]);
        $destination = Project::factory()->create(['organization_id' => $organization->id]);
        $segment = Segment::factory()->create(['project_id' => $otherSource->id]);

        $response = $this->actingAs($user)->post(route('projects.segments.copy', $source), [
            'destination_project_id' => $destination->id,
            'segment_ids' => [$segment->id],
        ]);

        $response->assertSessionHasErrors('segment_ids.0');
        $this->assertFalse($destination->segments()->where('slug', $segment->slug)->exists());
    }

    public function test_destination_project_must_differ_from_source_project(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);
        $segment = Segment::factory()->create(['project_id' => $project->id]);

        $response = $this->actingAs($user)->post(route('projects.segments.copy', $project), [
            'destination_project_id' => $project->id,
            'segment_ids' => [$segment->id],
        ]);

        $response->assertSessionHasErrors('destination_project_id');
    }
}
