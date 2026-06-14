<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectFavoriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_favorite_and_unfavorite_an_accessible_project(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($user)
            ->post(route('projects.favorite.store', $project))
            ->assertRedirect();

        $this->assertDatabaseHas('favorite_projects', [
            'user_id' => $user->id,
            'project_id' => $project->id,
        ]);

        $this->actingAs($user)
            ->delete(route('projects.favorite.destroy', $project))
            ->assertRedirect();

        $this->assertDatabaseMissing('favorite_projects', [
            'user_id' => $user->id,
            'project_id' => $project->id,
        ]);
    }

    public function test_favoriting_a_project_is_idempotent(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($user)->post(route('projects.favorite.store', $project));
        $this->actingAs($user)->post(route('projects.favorite.store', $project));

        $this->assertDatabaseCount('favorite_projects', 1);
    }

    public function test_favorites_are_personal_to_each_user(): void
    {
        ['user' => $firstUser, 'organization' => $organization] = $this->createUserWithOrganization();
        $secondUser = User::factory()->create();
        $organization->members()->attach($secondUser, ['role' => OrganizationRole::Guest->value]);
        $project = Project::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($firstUser)->post(route('projects.favorite.store', $project));

        $this->assertTrue($firstUser->favoriteProjects()->whereKey($project->id)->exists());
        $this->assertFalse($secondUser->favoriteProjects()->whereKey($project->id)->exists());
    }

    public function test_user_cannot_favorite_an_inaccessible_project(): void
    {
        ['user' => $user] = $this->createUserWithOrganization();
        $otherOrganization = Organization::factory()->create();
        $project = Project::factory()->create(['organization_id' => $otherOrganization->id]);

        $this->actingAs($user)
            ->post(route('projects.favorite.store', $project))
            ->assertForbidden();

        $this->assertDatabaseCount('favorite_projects', 0);
    }
}
