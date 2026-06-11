<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\Project;
use App\Models\RuleTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RuleTemplateCopyTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_copy_rule_templates_to_another_project(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $source = Project::factory()->create(['organization_id' => $organization->id]);
        $destination = Project::factory()->create(['organization_id' => $organization->id]);
        $template = RuleTemplate::factory()->create([
            'project_id' => $source->id,
            'name' => 'Newsletter subscribers',
        ]);

        $response = $this->actingAs($user)->post(route('projects.rule-templates.copy', $source), [
            'destination_project_id' => $destination->id,
            'rule_template_ids' => [$template->id],
        ]);

        $this->assertDatabaseHas('rule_templates', [
            'project_id' => $destination->id,
            'name' => $template->name,
            'type' => $template->type,
            'key' => $template->key,
            'operator' => $template->operator,
            'value' => $template->value,
        ]);
        $response->assertRedirect(route('projects.rule-templates.index', $source));
        $response->assertSessionHas('ruleTemplateCopy', fn (array $result) => filled($result['id'])
            && $result['message'] === "1 rule template copied into {$destination->name}."
            && $result['destination_name'] === $destination->name
            && $result['destination_url'] === route('projects.rule-templates.index', $destination));
    }

    public function test_copy_skips_names_that_already_exist_in_destination(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $source = Project::factory()->create(['organization_id' => $organization->id]);
        $destination = Project::factory()->create(['organization_id' => $organization->id]);
        $conflict = $source->ruleTemplates()->where('name', 'Google visitors')->firstOrFail();
        $copyable = RuleTemplate::factory()->create([
            'project_id' => $source->id,
            'name' => 'Newsletter subscribers',
        ]);

        $response = $this->actingAs($user)->post(route('projects.rule-templates.copy', $source), [
            'destination_project_id' => $destination->id,
            'rule_template_ids' => [$conflict->id, $copyable->id],
        ]);

        $this->assertCount(1, $destination->ruleTemplates()->where('name', 'Google visitors')->get());
        $this->assertTrue($destination->ruleTemplates()->where('name', 'Newsletter subscribers')->exists());
        $response->assertSessionHas('ruleTemplateCopy', fn (array $result) => $result['message'] === "1 rule template copied into {$destination->name}. 1 rule template skipped because the name already exists.");
    }

    public function test_user_cannot_copy_to_a_project_they_cannot_manage(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $source = Project::factory()->create(['organization_id' => $organization->id]);
        $template = $source->ruleTemplates()->firstOrFail();
        $viewerOrganization = Organization::factory()->create();
        $viewerOrganization->members()->attach($user, ['role' => OrganizationRole::Viewer->value]);
        $destination = Project::factory()->create(['organization_id' => $viewerOrganization->id]);

        $response = $this->actingAs($user)->post(route('projects.rule-templates.copy', $source), [
            'destination_project_id' => $destination->id,
            'rule_template_ids' => [$template->id],
        ]);

        $response->assertForbidden();
    }

    public function test_user_cannot_copy_rule_templates_from_another_source_project(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $source = Project::factory()->create(['organization_id' => $organization->id]);
        $otherSource = Project::factory()->create(['organization_id' => $organization->id]);
        $destination = Project::factory()->create(['organization_id' => $organization->id]);
        $template = $otherSource->ruleTemplates()->firstOrFail();

        $response = $this->actingAs($user)->post(route('projects.rule-templates.copy', $source), [
            'destination_project_id' => $destination->id,
            'rule_template_ids' => [$template->id],
        ]);

        $response->assertSessionHasErrors('rule_template_ids.0');
    }

    public function test_destination_project_must_differ_from_source_project(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);
        $template = $project->ruleTemplates()->firstOrFail();

        $response = $this->actingAs($user)->post(route('projects.rule-templates.copy', $project), [
            'destination_project_id' => $project->id,
            'rule_template_ids' => [$template->id],
        ]);

        $response->assertSessionHasErrors('destination_project_id');
    }
}
