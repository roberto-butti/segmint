<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\RuleTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('projects.create'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_create_form(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();

        $this->actingAs($user);

        $response = $this->get(route('projects.create'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Projects/Create')
            ->has('organizations', 1)
        );
    }

    public function test_create_form_only_shows_orgs_with_manage_permission(): void
    {
        ['user' => $user, 'organization' => $ownedOrg] = $this->createUserWithOrganization();

        $viewerOrg = Organization::factory()->create();
        $viewerOrg->members()->attach($user, ['role' => OrganizationRole::Viewer->value]);

        $this->actingAs($user);

        $response = $this->get(route('projects.create'));

        $response->assertInertia(fn ($page) => $page
            ->has('organizations', 1)
            ->where('organizations.0.id', $ownedOrg->id)
        );
    }

    public function test_user_can_create_a_project(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();

        $this->actingAs($user);

        $response = $this->post(route('projects.store'), [
            'name' => 'My New Project',
            'description' => 'A test project',
            'organization_id' => $organization->id,
        ]);

        $this->assertDatabaseHas('projects', [
            'organization_id' => $organization->id,
            'name' => 'My New Project',
            'slug' => 'my-new-project',
            'description' => 'A test project',
            'active' => true,
        ]);

        $project = $organization->projects()->where('name', 'My New Project')->first();
        $response->assertRedirect(route('projects.show', $project));
    }

    public function test_user_cannot_create_project_in_org_they_dont_belong_to(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $otherOrg = Organization::factory()->create();

        $this->actingAs($user);

        $response = $this->post(route('projects.store'), [
            'name' => 'Sneaky Project',
            'organization_id' => $otherOrg->id,
        ]);

        $response->assertForbidden();
    }

    public function test_viewer_cannot_create_project(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();

        $viewerOrg = Organization::factory()->create();
        $viewerOrg->members()->attach($user, ['role' => OrganizationRole::Viewer->value]);

        $this->actingAs($user);

        $response = $this->post(route('projects.store'), [
            'name' => 'Viewer Project',
            'organization_id' => $viewerOrg->id,
        ]);

        $response->assertForbidden();
    }

    public function test_project_creation_auto_creates_rule_templates(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();

        $this->actingAs($user);

        $this->post(route('projects.store'), [
            'name' => 'Template Test Project',
            'organization_id' => $organization->id,
        ]);

        $project = $organization->projects()->where('name', 'Template Test Project')->first();

        $this->assertCount(count(RuleTemplate::defaults()), $project->ruleTemplates);
    }

    public function test_project_creation_auto_creates_access_token(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();

        $this->actingAs($user);

        $this->post(route('projects.store'), [
            'name' => 'Token Test Project',
            'organization_id' => $organization->id,
        ]);

        $project = $organization->projects()->where('name', 'Token Test Project')->first();

        $this->assertCount(1, $project->accessTokens);
        $this->assertDatabaseHas('access_tokens', [
            'project_id' => $project->id,
            'name' => 'Default',
            'active' => true,
        ]);
    }

    public function test_name_is_required(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();

        $this->actingAs($user);

        $response = $this->post(route('projects.store'), [
            'name' => '',
            'organization_id' => $organization->id,
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_project_can_be_created_without_description(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();

        $this->actingAs($user);

        $response = $this->post(route('projects.store'), [
            'name' => 'No Description Project',
            'organization_id' => $organization->id,
        ]);

        $project = $organization->projects()->where('name', 'No Description Project')->first();
        $this->assertNotNull($project);
        $this->assertNull($project->description);

        $response->assertRedirect(route('projects.show', $project));
    }
}
