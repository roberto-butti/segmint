<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\RuleTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RuleTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_templates_are_created_when_project_is_created(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $defaults = RuleTemplate::defaults();
        $this->assertCount(count($defaults), $project->ruleTemplates);

        foreach ($defaults as $default) {
            $this->assertDatabaseHas('rule_templates', [
                'project_id' => $project->id,
                'name' => $default['name'],
                'type' => $default['type'],
            ]);
        }
    }

    public function test_templates_are_passed_to_segment_create_page(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        $response = $this->get(route('projects.segments.create', $project));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Segments/Create')
            ->has('ruleTemplates', count(RuleTemplate::defaults()))
        );
    }

    public function test_templates_are_passed_to_segment_edit_page(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $segment = $project->segments()->create([
            'name' => 'Test',
            'slug' => 'test',
            'active' => true,
        ]);

        $this->actingAs($user);

        $response = $this->get(route('projects.segments.edit', [$project, $segment]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Segments/Edit')
            ->has('ruleTemplates', count(RuleTemplate::defaults()))
        );
    }

    public function test_templates_are_project_scoped(): void
    {
        $user = User::factory()->create();
        $project1 = Project::factory()->create(['user_id' => $user->id]);
        $project2 = Project::factory()->create(['user_id' => $user->id]);

        // Add a custom template to project1 only
        $project1->ruleTemplates()->create([
            'name' => 'Custom rule',
            'type' => 'comparison',
            'key' => 'custom.key',
            'operator' => '=',
            'value' => 'custom',
        ]);

        $defaultCount = count(RuleTemplate::defaults());

        $this->assertCount($defaultCount + 1, $project1->ruleTemplates);
        $this->assertCount($defaultCount, $project2->ruleTemplates);
    }

    public function test_templates_are_deleted_when_project_is_deleted(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $this->assertGreaterThan(0, $project->ruleTemplates()->count());

        $projectId = $project->id;
        $project->delete();

        $this->assertDatabaseMissing('rule_templates', ['project_id' => $projectId]);
    }
}
