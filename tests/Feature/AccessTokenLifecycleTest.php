<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessTokenLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_manager_can_create_an_additional_token_and_see_the_secret_once(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);

        $response = $this->actingAs($user)->post(route('projects.access-tokens.store', $project), [
            'name' => 'Production website',
        ]);

        $token = $project->accessTokens()->where('name', 'Production website')->firstOrFail();

        $response
            ->assertRedirect(route('projects.access-tokens.index', $project))
            ->assertSessionHas('accessTokenSecret', fn (array $secret) => $secret['access_token_id'] === $token->id
                && $secret['token'] === $token->token
                && $secret['action'] === 'created');

        $this->get(route('projects.access-tokens.index', $project))
            ->assertInertia(fn ($page) => $page
                ->where('flash.accessTokenSecret.token', $token->token)
                ->missing('accessTokens.0.token')
            );

        $this->get(route('projects.access-tokens.index', $project))
            ->assertInertia(fn ($page) => $page
                ->where('flash.accessTokenSecret', null)
            );
    }

    public function test_token_names_must_be_unique_within_a_project(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($user)->post(route('projects.access-tokens.store', $project), [
            'name' => 'Default',
        ])->assertSessionHasErrors('name');
    }

    public function test_project_manager_can_revoke_and_reactivate_a_token(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);
        $token = $project->accessTokens()->firstOrFail();

        $this->actingAs($user)->patch(route('projects.access-tokens.update', [$project, $token]), [
            'active' => false,
        ])->assertRedirect(route('projects.access-tokens.index', $project));

        $this->assertFalse($token->fresh()->active);

        $this->actingAs($user)->patch(route('projects.access-tokens.update', [$project, $token]), [
            'active' => true,
        ])->assertRedirect(route('projects.access-tokens.index', $project));

        $this->assertTrue($token->fresh()->active);
    }

    public function test_active_status_requires_a_strict_boolean(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);
        $token = $project->accessTokens()->firstOrFail();

        $this->actingAs($user)->patch(route('projects.access-tokens.update', [$project, $token]), [
            'active' => 'false',
        ])->assertSessionHasErrors('active');

        $this->assertTrue($token->fresh()->active);
    }

    public function test_project_manager_can_rotate_a_token_and_old_value_stops_working(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);
        $token = $project->accessTokens()->firstOrFail();
        $oldValue = $token->token;

        $response = $this->actingAs($user)
            ->post(route('projects.access-tokens.rotate', [$project, $token]));

        $newValue = $token->fresh()->token;

        $response
            ->assertRedirect(route('projects.access-tokens.index', $project))
            ->assertSessionHas('accessTokenSecret', fn (array $secret) => $secret['token'] === $newValue
                && $secret['action'] === 'rotated');

        $this->assertNotSame($oldValue, $newValue);
        $this->assertNull(Project::resolveFromAccessToken($oldValue));
        $this->assertTrue(Project::resolveFromAccessToken($newValue)->is($project));
    }

    public function test_rotating_a_revoked_token_does_not_reactivate_it(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);
        $token = $project->accessTokens()->firstOrFail();
        $token->update(['active' => false]);

        $this->actingAs($user)
            ->post(route('projects.access-tokens.rotate', [$project, $token]))
            ->assertRedirect(route('projects.access-tokens.index', $project));

        $this->assertFalse($token->fresh()->active);
    }

    public function test_successful_token_resolution_updates_last_used_at(): void
    {
        $project = Project::factory()->create();
        $token = $project->accessTokens()->firstOrFail();

        $this->assertNull($token->last_used_at);
        $this->assertTrue(Project::resolveFromAccessToken($token->token)->is($project));
        $this->assertNotNull($token->fresh()->last_used_at);
    }

    public function test_token_resolution_can_skip_last_used_update(): void
    {
        $project = Project::factory()->create();
        $token = $project->accessTokens()->firstOrFail();

        $this->assertTrue(Project::resolveFromAccessToken($token->token, markAsUsed: false)->is($project));
        $this->assertNull($token->fresh()->last_used_at);
    }

    public function test_active_segments_api_marks_the_token_as_used(): void
    {
        $project = Project::factory()->create();
        $token = $project->accessTokens()->firstOrFail();

        $this->getJson('/api/segments?token='.$token->token)->assertOk();

        $this->assertNotNull($token->fresh()->last_used_at);
    }

    public function test_viewer_can_inspect_token_metadata_but_cannot_manage_tokens(): void
    {
        $organization = Organization::factory()->create();
        $viewer = User::factory()->create();
        $organization->members()->attach($viewer, ['role' => OrganizationRole::Viewer->value]);
        $project = Project::factory()->create(['organization_id' => $organization->id]);
        $token = $project->accessTokens()->firstOrFail();

        $this->actingAs($viewer)
            ->get(route('projects.access-tokens.index', $project))
            ->assertInertia(fn ($page) => $page
                ->where('canManageProject', false)
                ->missing('accessTokens.0.token')
            );

        $this->actingAs($viewer)
            ->post(route('projects.access-tokens.store', $project), ['name' => 'Denied'])
            ->assertForbidden();
        $this->actingAs($viewer)
            ->patch(route('projects.access-tokens.update', [$project, $token]), ['active' => false])
            ->assertForbidden();
        $this->actingAs($viewer)
            ->post(route('projects.access-tokens.rotate', [$project, $token]))
            ->assertForbidden();
    }

    public function test_token_actions_reject_a_token_from_another_project(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);
        $otherProject = Project::factory()->create(['organization_id' => $organization->id]);
        $otherToken = $otherProject->accessTokens()->firstOrFail();

        $this->actingAs($user)
            ->patch(route('projects.access-tokens.update', [$project, $otherToken]), ['active' => false])
            ->assertNotFound();
        $this->actingAs($user)
            ->post(route('projects.access-tokens.rotate', [$project, $otherToken]))
            ->assertNotFound();
    }
}
