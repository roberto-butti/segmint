<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Segment;
use App\Models\SegmentRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SegmentShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $project = Project::factory()->create();
        $segment = Segment::factory()->create(['project_id' => $project->id]);

        $response = $this->get(route('projects.segments.show', [$project, $segment]));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_segment(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);
        $segment = Segment::factory()->create(['project_id' => $project->id]);

        $this->actingAs($user);

        $response = $this->get(route('projects.segments.show', [$project, $segment]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Segments/Show')
            ->where('project.id', $project->id)
            ->where('segment.id', $segment->id)
            ->has('ruleTypes')
            ->has('ruleOperators')
        );
    }

    public function test_user_cannot_view_segment_for_another_users_project(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $otherProject = Project::factory()->create();
        $segment = Segment::factory()->create(['project_id' => $otherProject->id]);

        $this->actingAs($user);

        $response = $this->get(route('projects.segments.show', [$otherProject, $segment]));
        $response->assertForbidden();
    }

    public function test_user_cannot_view_segment_from_different_project(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);
        $otherProject = Project::factory()->create(['organization_id' => $organization->id]);
        $segment = Segment::factory()->create(['project_id' => $otherProject->id]);

        $this->actingAs($user);

        $response = $this->get(route('projects.segments.show', [$project, $segment]));
        $response->assertNotFound();
    }

    public function test_show_page_loads_rules(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);
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

        $response = $this->get(route('projects.segments.show', [$project, $segment]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Segments/Show')
            ->has('segment.rules', 1)
        );
    }
}
