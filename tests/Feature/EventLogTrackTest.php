<?php

namespace Tests\Feature;

use App\Models\EventLog;
use App\Models\Project;
use App\Models\Segment;
use App\Models\SegmentMatch;
use App\Models\SegmentRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventLogTrackTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_returns_segments_without_persisting_event_or_matches(): void
    {
        [$project, $token] = $this->createProjectWithToken();
        $segment = Segment::factory()->create([
            'project_id' => $project->id,
            'slug' => 'google-visitors',
        ]);
        $this->createRule($segment, [
            'key' => 'utm_source',
            'value' => 'google',
        ]);

        $response = $this->postJson('/api/event-log/track', [
            ...$this->eventPayload($token),
            'dry_run' => true,
            'utms' => ['utm_source' => 'google'],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('dry_run', true)
            ->assertJsonPath('segments.0.slug', 'google-visitors');

        $this->assertDatabaseCount('event_logs', 0);
        $this->assertDatabaseCount('segment_matches', 0);
        $this->assertNull($project->accessTokens()->firstOrFail()->last_used_at);
    }

    public function test_dry_run_count_rules_include_candidate_without_changing_stored_counts(): void
    {
        [$project, $token] = $this->createProjectWithToken();
        $segment = Segment::factory()->create([
            'project_id' => $project->id,
            'slug' => 'frequent-visitors',
        ]);
        $this->createRule($segment, [
            'type' => 'visit_count',
            'key' => 'page-view',
            'operator' => '>=',
            'value' => '2',
        ]);
        $this->createRule($segment, [
            'type' => 'page_view_count',
            'key' => 'page_path',
            'operator' => '>=',
            'value' => '2',
            'priority' => 1,
        ]);

        EventLog::create([
            'project_id' => $project->id,
            'visitor_id' => 'visitor-1',
            'event_type' => 'page-view',
            'page_path' => '/pricing',
        ]);

        $this->postJson('/api/event-log/track', [
            ...$this->eventPayload($token),
            'dry_run' => true,
        ])
            ->assertOk()
            ->assertJsonPath('segments.0.slug', 'frequent-visitors');

        $this->assertSame(1, EventLog::where('visitor_id', 'visitor-1')->count());
        $this->assertDatabaseCount('segment_matches', 0);
    }

    public function test_count_rules_ignore_events_from_other_projects(): void
    {
        [$project, $token] = $this->createProjectWithToken();
        $otherProject = Project::factory()->create();
        $segment = Segment::factory()->create([
            'project_id' => $project->id,
            'slug' => 'frequent-visitors',
        ]);
        $this->createRule($segment, [
            'type' => 'visit_count',
            'key' => 'page-view',
            'operator' => '>=',
            'value' => '2',
        ]);
        $this->createRule($segment, [
            'type' => 'page_view_count',
            'key' => 'page_path',
            'operator' => '>=',
            'value' => '2',
            'priority' => 1,
        ]);

        EventLog::create([
            'project_id' => $otherProject->id,
            'visitor_id' => 'visitor-1',
            'event_type' => 'page-view',
            'page_path' => '/pricing',
        ]);

        $this->postJson('/api/event-log/track', [
            ...$this->eventPayload($token),
            'dry_run' => true,
        ])
            ->assertOk()
            ->assertJsonPath('segments', []);
    }

    public function test_dry_run_and_real_event_return_the_same_segments_for_the_same_state(): void
    {
        [$project, $token] = $this->createProjectWithToken();
        $segment = Segment::factory()->create([
            'project_id' => $project->id,
            'slug' => 'second-visit',
        ]);
        $this->createRule($segment, [
            'type' => 'visit_count',
            'key' => 'page-view',
            'operator' => '>=',
            'value' => '2',
        ]);

        EventLog::create([
            'project_id' => $project->id,
            'visitor_id' => 'visitor-1',
            'event_type' => 'page-view',
            'page_path' => '/pricing',
        ]);

        $drySegments = $this->postJson('/api/event-log/track', [
            ...$this->eventPayload($token),
            'dry_run' => true,
        ])->json('segments');

        $realSegments = $this
            ->postJson('/api/event-log/track', $this->eventPayload($token))
            ->json('segments');

        $this->assertSame(
            collect($realSegments)->pluck('slug')->all(),
            collect($drySegments)->pluck('slug')->all(),
        );
    }

    public function test_omitting_dry_run_preserves_event_and_match_persistence(): void
    {
        [$project, $token] = $this->createProjectWithToken();
        $segment = Segment::factory()->create(['project_id' => $project->id]);
        $this->createRule($segment);

        $this->postJson('/api/event-log/track', $this->eventPayload($token))
            ->assertOk()
            ->assertJsonMissingPath('dry_run');

        $this->assertDatabaseCount('event_logs', 1);
        $this->assertDatabaseCount('segment_matches', 1);
        $this->assertTrue(SegmentMatch::firstOrFail()->matched);
        $this->assertNotNull($project->accessTokens()->firstOrFail()->last_used_at);
    }

    public function test_false_dry_run_preserves_normal_tracking_behavior(): void
    {
        [, $token] = $this->createProjectWithToken();

        $this->postJson('/api/event-log/track', [
            ...$this->eventPayload($token),
            'dry_run' => false,
        ])
            ->assertOk()
            ->assertJsonMissingPath('dry_run');

        $this->assertDatabaseCount('event_logs', 1);
    }

    public function test_dry_run_must_be_a_strict_boolean(): void
    {
        [, $token] = $this->createProjectWithToken();

        $this->postJson('/api/event-log/track', [
            ...$this->eventPayload($token),
            'dry_run' => 'true',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('dry_run');

        $this->assertDatabaseCount('event_logs', 0);
    }

    public function test_dry_run_does_not_bypass_token_validation(): void
    {
        $this->postJson('/api/event-log/track', [
            ...$this->eventPayload('invalid-token'),
            'dry_run' => true,
        ])->assertNotFound();

        $this->assertDatabaseCount('event_logs', 0);
    }

    /**
     * @return array{Project, string}
     */
    private function createProjectWithToken(): array
    {
        $project = Project::factory()->create();

        return [$project, $project->accessTokens()->firstOrFail()->token];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createRule(Segment $segment, array $attributes = []): SegmentRule
    {
        return SegmentRule::create(array_merge([
            'segment_id' => $segment->id,
            'type' => 'comparison',
            'key' => 'event_type',
            'operator' => '=',
            'value' => 'page-view',
            'priority' => 0,
        ], $attributes));
    }

    /**
     * @return array<string, mixed>
     */
    private function eventPayload(string $token): array
    {
        return [
            'token' => $token,
            'visitor_id' => 'visitor-1',
            'type' => 'page-view',
            'url' => 'https://example.com/pricing',
            'path' => '/pricing',
            'event_properties' => [],
            'metadata' => [],
        ];
    }
}
