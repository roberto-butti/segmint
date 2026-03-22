<?php

namespace Tests\Feature;

use App\Models\EventLog;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SegmentSuggestionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $project = Project::factory()->create();

        $this->get(route('projects.segments.suggestions', $project))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_suggestions(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($user);

        $response = $this->get(route('projects.segments.suggestions', $project));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Segments/Suggestions')
            ->where('project.id', $project->id)
            ->has('suggestions')
        );
    }

    public function test_user_cannot_view_suggestions_for_another_users_project(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $otherProject = Project::factory()->create();

        $this->actingAs($user);

        $this->get(route('projects.segments.suggestions', $otherProject))
            ->assertForbidden();
    }

    public function test_suggestions_are_generated_from_utm_source(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);

        for ($i = 0; $i < 5; $i++) {
            EventLog::create([
                'project_id' => $project->id,
                'uuid' => uniqid('', true),
                'event_type' => 'page-view',
                'utm_source' => 'google',
            ]);
        }

        $this->actingAs($user);

        $response = $this->get(route('projects.segments.suggestions', $project));

        $response->assertInertia(fn ($page) => $page
            ->where('suggestions.0.name', 'Google Visitors')
            ->where('suggestions.0.rules.0.key', 'utm_source')
            ->where('suggestions.0.rules.0.value', 'google')
        );
    }

    public function test_existing_segments_are_marked_as_exists(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);

        for ($i = 0; $i < 5; $i++) {
            EventLog::create([
                'project_id' => $project->id,
                'uuid' => uniqid('', true),
                'event_type' => 'page-view',
                'utm_source' => 'google',
            ]);
        }

        $segment = $project->segments()->create([
            'name' => 'My Google Segment',
            'slug' => 'my-google-segment',
            'active' => true,
        ]);
        $segment->rules()->create([
            'type' => 'comparison',
            'key' => 'utm_source',
            'operator' => '=',
            'value' => 'google',
            'priority' => 0,
        ]);

        $this->actingAs($user);

        $response = $this->get(route('projects.segments.suggestions', $project));

        $response->assertInertia(function ($page) {
            $suggestions = $page->toArray()['props']['suggestions'];
            // Find the google suggestion by checking rules, not slug
            $googleSuggestion = collect($suggestions)->first(fn ($s) => collect($s['rules'])->contains(fn ($r) => $r['key'] === 'utm_source' && $r['value'] === 'google'
            ));
            $this->assertNotNull($googleSuggestion);
            $this->assertEquals('exists', $googleSuggestion['status']);
            $this->assertEquals('My Google Segment', $googleSuggestion['existingSegment']['name']);
        });
    }

    public function test_similar_segments_are_marked_as_similar(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);

        for ($i = 0; $i < 5; $i++) {
            EventLog::create([
                'project_id' => $project->id,
                'uuid' => uniqid('', true),
                'event_type' => 'page-view',
                'utm_source' => 'google',
            ]);
        }

        // Create a segment with same type/key/operator but different value
        $segment = $project->segments()->create([
            'name' => 'Facebook Visitors',
            'slug' => 'facebook_visitors',
            'active' => true,
        ]);
        $segment->rules()->create([
            'type' => 'comparison',
            'key' => 'utm_source',
            'operator' => '=',
            'value' => 'facebook',
            'priority' => 0,
        ]);

        $this->actingAs($user);

        $response = $this->get(route('projects.segments.suggestions', $project));

        $response->assertInertia(function ($page) {
            $suggestions = $page->toArray()['props']['suggestions'];
            $googleSuggestion = collect($suggestions)->firstWhere('slug', 'google_visitors');
            $this->assertNotNull($googleSuggestion);
            $this->assertEquals('similar', $googleSuggestion['status']);
            $this->assertEquals('facebook', $googleSuggestion['existingSegment']['matchingRule']['value']);
        });
    }

    public function test_empty_suggestions_when_no_events(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($user);

        $response = $this->get(route('projects.segments.suggestions', $project));

        $response->assertInertia(fn ($page) => $page
            ->has('suggestions', 0)
        );
    }
}
