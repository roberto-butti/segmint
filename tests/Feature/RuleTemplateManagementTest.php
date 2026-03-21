<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\RuleTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RuleTemplateManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $project = Project::factory()->create();

        $this->get(route('projects.rule-templates.index', $project))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_templates(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($user);

        $response = $this->get(route('projects.rule-templates.index', $project));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('RuleTemplates/Index')
            ->where('project.id', $project->id)
            ->has('templates', count(RuleTemplate::defaults()))
            ->has('ruleTypes')
            ->has('ruleOperators')
        );
    }

    public function test_user_cannot_view_templates_for_another_users_project(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $otherProject = Project::factory()->create();

        $this->actingAs($user);

        $this->get(route('projects.rule-templates.index', $otherProject))
            ->assertForbidden();
    }

    public function test_user_can_create_a_template(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($user);

        $response = $this->post(route('projects.rule-templates.store', $project), [
            'name' => 'Newsletter subscribers',
            'type' => 'comparison',
            'key' => 'utms.utm_medium',
            'operator' => '=',
            'value' => 'email',
        ]);

        $response->assertRedirect(route('projects.rule-templates.index', $project->slug));

        $this->assertDatabaseHas('rule_templates', [
            'project_id' => $project->id,
            'name' => 'Newsletter subscribers',
            'type' => 'comparison',
            'key' => 'utms.utm_medium',
            'operator' => '=',
            'value' => 'email',
        ]);
    }

    public function test_user_can_create_template_without_value(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($user);

        $response = $this->post(route('projects.rule-templates.store', $project), [
            'name' => 'UTM Source match',
            'type' => 'comparison',
            'key' => 'utms.utm_source',
            'operator' => '=',
            'value' => '',
        ]);

        $response->assertRedirect(route('projects.rule-templates.index', $project->slug));

        $this->assertDatabaseHas('rule_templates', [
            'project_id' => $project->id,
            'name' => 'UTM Source match',
            'value' => '',
        ]);
    }

    public function test_name_is_required_for_create(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($user);

        $response = $this->post(route('projects.rule-templates.store', $project), [
            'name' => '',
            'type' => 'comparison',
            'key' => 'utms.utm_source',
            'operator' => '=',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_user_can_update_a_template(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);
        $template = $project->ruleTemplates()->first();

        $this->actingAs($user);

        $response = $this->put(route('projects.rule-templates.update', [$project, $template]), [
            'name' => 'Updated name',
            'type' => 'comparison',
            'key' => 'utms.utm_campaign',
            'operator' => 'contains',
            'value' => 'summer',
        ]);

        $response->assertRedirect(route('projects.rule-templates.index', $project->slug));

        $this->assertDatabaseHas('rule_templates', [
            'id' => $template->id,
            'name' => 'Updated name',
            'operator' => 'contains',
            'value' => 'summer',
        ]);
    }

    public function test_user_cannot_update_template_for_another_users_project(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $otherProject = Project::factory()->create();
        $template = $otherProject->ruleTemplates()->first();

        $this->actingAs($user);

        $response = $this->put(route('projects.rule-templates.update', [$otherProject, $template]), [
            'name' => 'Hacked',
            'type' => 'comparison',
            'key' => 'x',
            'operator' => '=',
        ]);

        $response->assertForbidden();
    }

    public function test_user_can_delete_a_template(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);
        $template = $project->ruleTemplates()->first();

        $this->actingAs($user);

        $response = $this->delete(route('projects.rule-templates.destroy', [$project, $template]));

        $response->assertRedirect(route('projects.rule-templates.index', $project->slug));
        $this->assertDatabaseMissing('rule_templates', ['id' => $template->id]);
    }

    public function test_user_cannot_delete_template_for_another_users_project(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $otherProject = Project::factory()->create();
        $template = $otherProject->ruleTemplates()->first();

        $this->actingAs($user);

        $response = $this->delete(route('projects.rule-templates.destroy', [$otherProject, $template]));

        $response->assertForbidden();
        $this->assertDatabaseHas('rule_templates', ['id' => $template->id]);
    }

    public function test_user_cannot_update_template_from_different_project(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);
        $otherProject = Project::factory()->create(['organization_id' => $organization->id]);
        $template = $otherProject->ruleTemplates()->first();

        $this->actingAs($user);

        $response = $this->put(route('projects.rule-templates.update', [$project, $template]), [
            'name' => 'Nope',
            'type' => 'comparison',
            'key' => 'x',
            'operator' => '=',
        ]);

        $response->assertNotFound();
    }
}
