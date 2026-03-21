<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Segment;
use App\Models\SegmentRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SegmentEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $project = Project::factory()->create();
        $segment = Segment::factory()->create(['project_id' => $project->id]);

        $response = $this->get(route('projects.segments.edit', [$project, $segment]));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_edit_form(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);
        $segment = Segment::factory()->create(['project_id' => $project->id]);

        $this->actingAs($user);

        $response = $this->get(route('projects.segments.edit', [$project, $segment]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Segments/Edit')
            ->where('project.id', $project->id)
            ->where('segment.id', $segment->id)
        );
    }

    public function test_user_cannot_view_edit_form_for_another_users_project(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $otherProject = Project::factory()->create();
        $segment = Segment::factory()->create(['project_id' => $otherProject->id]);

        $this->actingAs($user);

        $response = $this->get(route('projects.segments.edit', [$otherProject, $segment]));
        $response->assertForbidden();
    }

    public function test_user_cannot_edit_segment_from_different_project(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);
        $otherProject = Project::factory()->create(['organization_id' => $organization->id]);
        $segment = Segment::factory()->create(['project_id' => $otherProject->id]);

        $this->actingAs($user);

        $response = $this->get(route('projects.segments.edit', [$project, $segment]));
        $response->assertNotFound();
    }

    public function test_user_can_update_a_segment(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);
        $segment = Segment::factory()->create([
            'project_id' => $project->id,
            'name' => 'Old Name',
            'active' => true,
        ]);

        $this->actingAs($user);

        $response = $this->put(route('projects.segments.update', [$project, $segment]), [
            'name' => 'Updated Name',
            'description' => 'Updated description',
            'active' => false,
        ]);

        $response->assertRedirect(route('projects.segments.index', $project));

        $this->assertDatabaseHas('segments', [
            'id' => $segment->id,
            'name' => 'Updated Name',
            'slug' => 'updated-name',
            'description' => 'Updated description',
            'active' => false,
        ]);
    }

    public function test_user_cannot_update_segment_for_another_users_project(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $otherProject = Project::factory()->create();
        $segment = Segment::factory()->create(['project_id' => $otherProject->id]);

        $this->actingAs($user);

        $response = $this->put(route('projects.segments.update', [$otherProject, $segment]), [
            'name' => 'Hacked',
            'active' => true,
        ]);

        $response->assertForbidden();
    }

    public function test_name_is_required_on_update(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);
        $segment = Segment::factory()->create(['project_id' => $project->id]);

        $this->actingAs($user);

        $response = $this->put(route('projects.segments.update', [$project, $segment]), [
            'name' => '',
            'active' => true,
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_user_can_update_segment_with_rules(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);
        $segment = Segment::factory()->create(['project_id' => $project->id]);

        $this->actingAs($user);

        $response = $this->put(route('projects.segments.update', [$project, $segment]), [
            'name' => $segment->name,
            'active' => true,
            'rules' => [
                [
                    'type' => 'comparison',
                    'key' => 'utm_source',
                    'operator' => '=',
                    'value' => 'google',
                    'priority' => 0,
                ],
            ],
        ]);

        $response->assertRedirect(route('projects.segments.index', $project));

        $this->assertCount(1, $segment->fresh()->rules);
        $this->assertDatabaseHas('segment_rules', [
            'segment_id' => $segment->id,
            'type' => 'comparison',
            'key' => 'utm_source',
            'operator' => '=',
            'value' => 'google',
        ]);
    }

    public function test_updating_rules_replaces_existing_rules(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);
        $segment = Segment::factory()->create(['project_id' => $project->id]);

        SegmentRule::create([
            'segment_id' => $segment->id,
            'type' => 'comparison',
            'key' => 'old_key',
            'operator' => '=',
            'value' => 'old_value',
            'priority' => 0,
        ]);

        $this->actingAs($user);

        $response = $this->put(route('projects.segments.update', [$project, $segment]), [
            'name' => $segment->name,
            'active' => true,
            'rules' => [
                [
                    'type' => 'browser_language',
                    'key' => 'lang',
                    'operator' => '=',
                    'value' => 'en',
                    'priority' => 0,
                ],
            ],
        ]);

        $response->assertRedirect(route('projects.segments.index', $project));

        $this->assertCount(1, $segment->fresh()->rules);
        $this->assertDatabaseMissing('segment_rules', ['key' => 'old_key']);
        $this->assertDatabaseHas('segment_rules', [
            'segment_id' => $segment->id,
            'type' => 'browser_language',
            'key' => 'lang',
            'value' => 'en',
        ]);
    }

    public function test_updating_with_empty_rules_removes_all_rules(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);
        $segment = Segment::factory()->create(['project_id' => $project->id]);

        SegmentRule::create([
            'segment_id' => $segment->id,
            'type' => 'comparison',
            'key' => 'test_key',
            'operator' => '=',
            'value' => 'test_value',
            'priority' => 0,
        ]);

        $this->actingAs($user);

        $response = $this->put(route('projects.segments.update', [$project, $segment]), [
            'name' => $segment->name,
            'active' => true,
            'rules' => [],
        ]);

        $response->assertRedirect(route('projects.segments.index', $project));
        $this->assertCount(0, $segment->fresh()->rules);
    }

    public function test_edit_form_loads_existing_rules(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);
        $segment = Segment::factory()->create(['project_id' => $project->id]);

        SegmentRule::create([
            'segment_id' => $segment->id,
            'type' => 'comparison',
            'key' => 'utm_source',
            'operator' => '=',
            'value' => 'facebook',
            'priority' => 0,
        ]);

        $this->actingAs($user);

        $response = $this->get(route('projects.segments.edit', [$project, $segment]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Segments/Edit')
            ->has('segment.rules', 1)
            ->has('ruleTypes')
            ->has('ruleOperators')
        );
    }
}
