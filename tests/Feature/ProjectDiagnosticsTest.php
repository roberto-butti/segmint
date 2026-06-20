<?php

namespace Tests\Feature;

use App\Models\DiagnosticScenario;
use App\Models\EventLog;
use App\Models\Project;
use App\Models\Segment;
use App\Models\SegmentRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectDiagnosticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $project = Project::factory()->create();

        $this->get(route('projects.diagnostics.index', $project))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_diagnostics(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($user)
            ->get(route('projects.diagnostics.index', $project))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Projects/Diagnostics')
                ->where('evaluatedAt', null)
                ->where('project.id', $project->id)
                ->where('diagnostics', null)
                ->where('payload.type', 'page-view')
            );
    }

    public function test_user_cannot_view_diagnostics_for_another_users_project(): void
    {
        ['user' => $user] = $this->createUserWithOrganization();
        $otherProject = Project::factory()->create();

        $this->actingAs($user)
            ->get(route('projects.diagnostics.index', $otherProject))
            ->assertForbidden();
    }

    public function test_diagnostics_explain_matched_and_failed_rules_without_persisting(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);
        $matched = Segment::factory()->create([
            'project_id' => $project->id,
            'name' => 'Google Visitors',
            'slug' => 'google-visitors',
        ]);
        $failed = Segment::factory()->create([
            'project_id' => $project->id,
            'name' => 'Facebook Visitors',
            'slug' => 'facebook-visitors',
        ]);
        $this->createRule($matched, ['key' => 'utm_source', 'value' => 'google']);
        $this->createRule($failed, ['key' => 'utm_source', 'value' => 'facebook']);

        $response = $this->actingAs($user)
            ->post(route('projects.diagnostics.evaluate', $project), [
                ...$this->payload(),
                'utms' => ['utm_source' => 'google'],
            ]);

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Projects/Diagnostics')
                ->whereNot('evaluatedAt', null)
                ->where('diagnostics.0.slug', 'facebook-visitors')
                ->where('diagnostics.0.matched', false)
                ->where('diagnostics.0.rules.0.actual', 'google')
                ->where('diagnostics.0.rules.0.passed', false)
                ->where('diagnostics.1.slug', 'google-visitors')
                ->where('diagnostics.1.matched', true)
                ->where('diagnostics.1.rules.0.actual', 'google')
                ->where('diagnostics.1.rules.0.passed', true)
            );

        $this->assertDatabaseCount('event_logs', 0);
        $this->assertDatabaseCount('segment_matches', 0);
    }

    public function test_diagnostics_include_existing_counts_and_candidate_event_without_mutation(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);
        $segment = Segment::factory()->create([
            'project_id' => $project->id,
            'name' => 'Frequent Pricing Visitors',
            'slug' => 'frequent-pricing-visitors',
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

        $this->actingAs($user)
            ->post(route('projects.diagnostics.evaluate', $project), $this->payload())
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('diagnostics.0.slug', 'frequent-pricing-visitors')
                ->where('diagnostics.0.matched', true)
                ->where('diagnostics.0.rules.0.actual', 2)
                ->where('diagnostics.0.rules.1.actual', 2)
            );

        $this->assertSame(1, EventLog::where('visitor_id', 'visitor-1')->count());
        $this->assertDatabaseCount('segment_matches', 0);
    }

    public function test_diagnostics_count_rules_ignore_events_from_other_projects(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);
        $otherProject = Project::factory()->create();
        $segment = Segment::factory()->create([
            'project_id' => $project->id,
            'name' => 'Frequent Pricing Visitors',
            'slug' => 'frequent-pricing-visitors',
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

        $this->actingAs($user)
            ->post(route('projects.diagnostics.evaluate', $project), $this->payload())
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('diagnostics.0.slug', 'frequent-pricing-visitors')
                ->where('diagnostics.0.matched', false)
                ->where('diagnostics.0.rules.0.actual', 1)
                ->where('diagnostics.0.rules.1.actual', 1)
            );
    }

    public function test_diagnostics_can_evaluate_browser_language_rules(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);
        $segment = Segment::factory()->create([
            'project_id' => $project->id,
            'slug' => 'english-visitors',
        ]);
        $this->createRule($segment, [
            'type' => 'browser_language',
            'key' => 'Accept-Language',
            'operator' => '=',
            'value' => 'en',
        ]);

        $this->actingAs($user)
            ->post(route('projects.diagnostics.evaluate', $project), [
                ...$this->payload(),
                'accept_language' => 'en-US,en;q=0.9,it;q=0.8',
            ])
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('diagnostics.0.slug', 'english-visitors')
                ->where('diagnostics.0.matched', true)
                ->where('diagnostics.0.rules.0.actual', 'en-US,en;q=0.9,it;q=0.8')
            );
    }

    public function test_diagnostics_use_current_browser_language_header_when_input_is_empty(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);
        $segment = Segment::factory()->create([
            'project_id' => $project->id,
            'slug' => 'italian-visitors',
        ]);
        $this->createRule($segment, [
            'type' => 'browser_language',
            'key' => 'Accept-Language',
            'operator' => '=',
            'value' => 'it',
        ]);

        $this->withHeaders(['Accept-Language' => 'it-IT,it;q=0.9,en;q=0.8'])
            ->actingAs($user)
            ->post(route('projects.diagnostics.evaluate', $project), [
                ...$this->payload(),
                'accept_language' => '',
            ])
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('payload.accept_language', 'it-IT,it;q=0.9,en;q=0.8')
                ->where('diagnostics.0.slug', 'italian-visitors')
                ->where('diagnostics.0.matched', true)
                ->where('diagnostics.0.rules.0.actual', 'it-IT,it;q=0.9,en;q=0.8')
            );
    }

    public function test_saved_scenarios_are_passed_to_diagnostics_page(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);
        DiagnosticScenario::create([
            'project_id' => $project->id,
            'name' => 'Pricing visitor from Google - EN',
            'payload' => $this->payload(),
            'last_result' => [],
            'last_run_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('projects.diagnostics.index', $project))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('savedScenarios.0.name', 'Pricing visitor from Google - EN')
                ->where('savedScenarios.0.payload.url', 'https://example.com/pricing')
                ->where('savedScenarios.0.last_result', [])
            );
    }

    public function test_user_can_save_diagnostic_scenario_with_last_result(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);
        $segment = Segment::factory()->create([
            'project_id' => $project->id,
            'slug' => 'google-visitors',
        ]);
        $this->createRule($segment, ['key' => 'utm_source', 'value' => 'google']);

        $this->actingAs($user)
            ->post(route('projects.diagnostics.scenarios.store', $project), [
                'name' => 'Pricing visitor from Google - EN',
                'payload' => [
                    ...$this->payload(),
                    'utms' => ['utm_source' => 'google'],
                ],
            ])
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('diagnostics.0.slug', 'google-visitors')
                ->where('diagnostics.0.matched', true)
                ->where('savedScenarios.0.name', 'Pricing visitor from Google - EN')
                ->where('savedScenarios.0.last_result.0.slug', 'google-visitors')
                ->where('savedScenarios.0.last_result.0.matched', true)
            );

        $scenario = DiagnosticScenario::firstOrFail();

        $this->assertSame('Pricing visitor from Google - EN', $scenario->name);
        $this->assertSame('google', data_get($scenario->payload, 'utms.utm_source'));
        $this->assertSame('google-visitors', data_get($scenario->last_result, '0.slug'));
        $this->assertNotNull($scenario->last_run_at);
        $this->assertDatabaseCount('event_logs', 0);
        $this->assertDatabaseCount('segment_matches', 0);
    }

    public function test_user_can_rerun_saved_scenario_and_update_last_result(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);
        $segment = Segment::factory()->create([
            'project_id' => $project->id,
            'slug' => 'google-visitors',
        ]);
        $rule = $this->createRule($segment, ['key' => 'utm_source', 'value' => 'google']);
        $scenario = DiagnosticScenario::create([
            'project_id' => $project->id,
            'name' => 'Pricing visitor from Google - EN',
            'payload' => [
                ...$this->payload(),
                'utms' => ['utm_source' => 'google'],
            ],
            'last_result' => [['slug' => 'google-visitors', 'matched' => true]],
            'last_run_at' => now()->subDay(),
        ]);

        $rule->update(['value' => 'facebook']);

        $this->actingAs($user)
            ->post(route('projects.diagnostics.scenarios.run', [$project, $scenario]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('diagnostics.0.slug', 'google-visitors')
                ->where('diagnostics.0.matched', false)
                ->where('savedScenarios.0.last_result.0.slug', 'google-visitors')
                ->where('savedScenarios.0.last_result.0.matched', false)
            );

        $scenario->refresh();

        $this->assertFalse(data_get($scenario->last_result, '0.matched'));
    }

    public function test_user_can_update_saved_scenario_payload_and_last_result(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);
        $segment = Segment::factory()->create([
            'project_id' => $project->id,
            'slug' => 'facebook-visitors',
        ]);
        $this->createRule($segment, ['key' => 'utm_source', 'value' => 'facebook']);
        $scenario = DiagnosticScenario::create([
            'project_id' => $project->id,
            'name' => 'Social visitor',
            'payload' => [
                ...$this->payload(),
                'utms' => ['utm_source' => 'google'],
            ],
            'last_result' => [],
        ]);

        $this->actingAs($user)
            ->put(route('projects.diagnostics.scenarios.update', [$project, $scenario]), [
                'payload' => [
                    ...$this->payload(),
                    'utms' => ['utm_source' => 'facebook'],
                ],
            ])
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('diagnostics.0.slug', 'facebook-visitors')
                ->where('diagnostics.0.matched', true)
                ->where('savedScenarios.0.payload.utms.utm_source', 'facebook')
                ->where('savedScenarios.0.last_result.0.matched', true)
            );

        $scenario->refresh();

        $this->assertSame('facebook', data_get($scenario->payload, 'utms.utm_source'));
        $this->assertTrue(data_get($scenario->last_result, '0.matched'));
    }

    public function test_user_can_delete_saved_scenario(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);
        $scenario = DiagnosticScenario::create([
            'project_id' => $project->id,
            'name' => 'Pricing visitor from Google - EN',
            'payload' => $this->payload(),
        ]);

        $this->actingAs($user)
            ->delete(route('projects.diagnostics.scenarios.destroy', [$project, $scenario]))
            ->assertRedirect(route('projects.diagnostics.index', $project));

        $this->assertDatabaseMissing('diagnostic_scenarios', ['id' => $scenario->id]);
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
    private function payload(): array
    {
        return [
            'visitor_id' => 'visitor-1',
            'type' => 'page-view',
            'url' => 'https://example.com/pricing',
            'event_properties' => [],
            'metadata' => [],
        ];
    }
}
