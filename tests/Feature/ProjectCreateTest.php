<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\Project;
use App\Models\RuleTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $organization = Organization::factory()->create();

        $this->get(route('organizations.projects.create', $organization))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_create_form(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();

        $this->actingAs($user);

        $response = $this->get(route('organizations.projects.create', $organization));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Projects/Create')
            ->where('organization.id', $organization->id)
        );
    }

    public function test_viewer_cannot_view_create_form(): void
    {
        ['user' => $user] = $this->createUserWithOrganization();

        $viewerOrg = Organization::factory()->create();
        $viewerOrg->members()->attach($user, ['role' => OrganizationRole::Viewer->value]);

        $this->actingAs($user);

        $this->get(route('organizations.projects.create', $viewerOrg))
            ->assertForbidden();
    }

    public function test_user_can_create_a_project(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();

        $this->actingAs($user);

        $response = $this->post(route('organizations.projects.store', $organization), [
            'name' => 'My New Project',
            'description' => 'A test project',
        ]);

        $this->assertDatabaseHas('projects', [
            'organization_id' => $organization->id,
            'name' => 'My New Project',
            'description' => 'A test project',
            'active' => true,
        ]);

        $project = $organization->projects()->where('name', 'My New Project')->first();
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{12}$/', $project->public_id);
        $response
            ->assertRedirect(route('projects.access-tokens.index', $project))
            ->assertSessionHas('accessTokenSecret', fn (array $secret) => $secret['access_token_id'] === $project->accessTokens()->firstOrFail()->id
                && $secret['token'] === $project->accessTokens()->firstOrFail()->token);
    }

    public function test_projects_receive_globally_unique_public_ids(): void
    {
        $projects = Project::factory()->count(20)->create();

        $this->assertCount(20, $projects->pluck('public_id')->unique());
        $projects->each(fn ($project) => $this->assertMatchesRegularExpression(
            '/^[A-Za-z0-9]{12}$/',
            $project->public_id,
        ));
    }

    public function test_user_cannot_create_project_in_org_they_dont_belong_to(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $otherOrg = Organization::factory()->create();

        $this->actingAs($user);

        $response = $this->post(route('organizations.projects.store', $otherOrg), [
            'name' => 'Sneaky Project',
        ]);

        $response->assertForbidden();
    }

    public function test_viewer_cannot_create_project(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();

        $viewerOrg = Organization::factory()->create();
        $viewerOrg->members()->attach($user, ['role' => OrganizationRole::Viewer->value]);

        $this->actingAs($user);

        $response = $this->post(route('organizations.projects.store', $viewerOrg), [
            'name' => 'Viewer Project',
        ]);

        $response->assertForbidden();
    }

    public function test_project_creation_auto_creates_rule_templates(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();

        $this->actingAs($user);

        $this->post(route('organizations.projects.store', $organization), [
            'name' => 'Template Test Project',
        ]);

        $project = $organization->projects()->where('name', 'Template Test Project')->first();

        $this->assertCount(count(RuleTemplate::defaults()), $project->ruleTemplates);
    }

    public function test_project_creation_auto_creates_access_token(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();

        $this->actingAs($user);

        $this->post(route('organizations.projects.store', $organization), [
            'name' => 'Token Test Project',
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

        $response = $this->post(route('organizations.projects.store', $organization), [
            'name' => '',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_project_can_be_created_without_description(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();

        $this->actingAs($user);

        $response = $this->post(route('organizations.projects.store', $organization), [
            'name' => 'No Description Project',
        ]);

        $project = $organization->projects()->where('name', 'No Description Project')->first();
        $this->assertNotNull($project);
        $this->assertNull($project->description);

        $response->assertRedirect(route('projects.access-tokens.index', $project));
    }
}
