<?php

namespace Tests\Feature;

use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SegmentCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $project = Project::factory()->create();

        $response = $this->get(route('projects.segments.create', $project));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_create_form(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($user);

        $response = $this->get(route('projects.segments.create', $project));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Segments/Create')
            ->where('project.id', $project->id)
        );
    }

    public function test_user_cannot_view_create_form_for_another_users_project(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $otherProject = Project::factory()->create();

        $this->actingAs($user);

        $response = $this->get(route('projects.segments.create', $otherProject));
        $response->assertForbidden();
    }

    public function test_user_can_create_a_segment(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($user);

        $response = $this->post(route('projects.segments.store', $project), [
            'name' => 'High Value Users',
            'description' => 'Users who spend more than $100',
            'active' => true,
        ]);

        $response->assertRedirect(route('projects.segments.index', $project));

        $this->assertDatabaseHas('segments', [
            'project_id' => $project->id,
            'name' => 'High Value Users',
            'slug' => 'high-value-users',
            'description' => 'Users who spend more than $100',
            'active' => true,
        ]);
    }

    public function test_user_cannot_create_segment_for_another_users_project(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $otherProject = Project::factory()->create();

        $this->actingAs($user);

        $response = $this->post(route('projects.segments.store', $otherProject), [
            'name' => 'Test Segment',
            'active' => true,
        ]);

        $response->assertForbidden();
    }

    public function test_name_is_required(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($user);

        $response = $this->post(route('projects.segments.store', $project), [
            'name' => '',
            'active' => true,
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_segment_can_be_created_without_description(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($user);

        $response = $this->post(route('projects.segments.store', $project), [
            'name' => 'Simple Segment',
            'active' => false,
        ]);

        $response->assertRedirect(route('projects.segments.index', $project));

        $this->assertDatabaseHas('segments', [
            'project_id' => $project->id,
            'name' => 'Simple Segment',
            'slug' => 'simple-segment',
            'active' => false,
        ]);
    }

    public function test_segment_can_be_created_with_rules(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($user);

        $response = $this->post(route('projects.segments.store', $project), [
            'name' => 'UTM Facebook Users',
            'description' => 'Users coming from Facebook',
            'active' => true,
            'rules' => [
                [
                    'type' => 'comparison',
                    'key' => 'utm_source',
                    'operator' => '=',
                    'value' => 'facebook',
                    'priority' => 0,
                ],
                [
                    'type' => 'visit_count',
                    'key' => 'page_views',
                    'operator' => '>',
                    'value' => '5',
                    'priority' => 1,
                ],
            ],
        ]);

        $response->assertRedirect(route('projects.segments.index', $project));

        $segment = $project->segments()->where('name', 'UTM Facebook Users')->first();
        $this->assertNotNull($segment);
        $this->assertCount(2, $segment->rules);

        $this->assertDatabaseHas('segment_rules', [
            'segment_id' => $segment->id,
            'type' => 'comparison',
            'key' => 'utm_source',
            'operator' => '=',
            'value' => 'facebook',
            'priority' => 0,
        ]);

        $this->assertDatabaseHas('segment_rules', [
            'segment_id' => $segment->id,
            'type' => 'visit_count',
            'key' => 'page_views',
            'operator' => '>',
            'value' => '5',
            'priority' => 1,
        ]);
    }

    public function test_rule_validation_requires_all_fields(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($user);

        $response = $this->post(route('projects.segments.store', $project), [
            'name' => 'Test Segment',
            'active' => true,
            'rules' => [
                [
                    'type' => '',
                    'key' => '',
                    'operator' => '',
                    'value' => '',
                ],
            ],
        ]);

        $response->assertSessionHasErrors([
            'rules.0.type',
            'rules.0.operator',
            'rules.0.value',
        ]);
    }

    public function test_rule_validation_rejects_invalid_type(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($user);

        $response = $this->post(route('projects.segments.store', $project), [
            'name' => 'Test Segment',
            'active' => true,
            'rules' => [
                [
                    'type' => 'invalid_type',
                    'key' => 'utm_source',
                    'operator' => '=',
                    'value' => 'facebook',
                ],
            ],
        ]);

        $response->assertSessionHasErrors('rules.0.type');
    }

    public function test_rule_validation_rejects_invalid_operator(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($user);

        $response = $this->post(route('projects.segments.store', $project), [
            'name' => 'Test Segment',
            'active' => true,
            'rules' => [
                [
                    'type' => 'comparison',
                    'key' => 'utm_source',
                    'operator' => 'invalid_op',
                    'value' => 'facebook',
                ],
            ],
        ]);

        $response->assertSessionHasErrors('rules.0.operator');
    }

    public function test_create_form_receives_enum_options(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($user);

        $response = $this->get(route('projects.segments.create', $project));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Segments/Create')
            ->has('ruleTypes')
            ->has('ruleOperators')
        );
    }
}
