<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Enums\ProjectRole;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectRoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_and_organization_admin_implicitly_administer_every_project(): void
    {
        ['user' => $owner, 'organization' => $organization] = $this->createUserWithOrganization();
        $admin = User::factory()->create();
        $organization->members()->attach($admin, ['role' => OrganizationRole::Admin->value]);
        $project = Project::factory()->create(['organization_id' => $organization->id]);

        $this->assertSame(ProjectRole::Admin, $owner->roleInProject($project));
        $this->assertSame(ProjectRole::Admin, $admin->roleInProject($project));
        $this->assertTrue($owner->can('manage', $project));
        $this->assertTrue($admin->can('manage', $project));
    }

    public function test_member_and_guest_require_an_explicit_project_assignment(): void
    {
        $organization = Organization::factory()->create();
        $member = User::factory()->create();
        $guest = User::factory()->create();
        $organization->members()->attach($member, ['role' => OrganizationRole::Member->value]);
        $organization->members()->attach($guest, ['role' => OrganizationRole::Guest->value]);
        $project = Project::factory()->create(['organization_id' => $organization->id]);

        $this->assertFalse($member->can('view', $project));
        $this->assertFalse($guest->can('view', $project));

        $member->assignedProjects()->attach($project, ['role' => ProjectRole::Viewer->value]);
        $guest->assignedProjects()->attach($project, ['role' => ProjectRole::Viewer->value]);

        $this->assertTrue($member->can('view', $project));
        $this->assertTrue($guest->can('view', $project));
    }

    public function test_project_roles_enforce_view_edit_and_manage_permissions(): void
    {
        $organization = Organization::factory()->create();
        $project = Project::factory()->create(['organization_id' => $organization->id]);

        $projectAdmin = $this->assignedMember($organization, $project, ProjectRole::Admin);
        $editor = $this->assignedMember($organization, $project, ProjectRole::Editor);
        $viewer = $this->assignedMember($organization, $project, ProjectRole::Viewer);

        $this->assertTrue($projectAdmin->can('view', $project));
        $this->assertTrue($projectAdmin->can('update', $project));
        $this->assertTrue($projectAdmin->can('manage', $project));

        $this->assertTrue($editor->can('view', $project));
        $this->assertTrue($editor->can('update', $project));
        $this->assertFalse($editor->can('manage', $project));

        $this->assertTrue($viewer->can('view', $project));
        $this->assertFalse($viewer->can('update', $project));
        $this->assertFalse($viewer->can('manage', $project));
    }

    public function test_project_admin_can_assign_roles_but_editor_cannot(): void
    {
        $organization = Organization::factory()->create();
        $project = Project::factory()->create(['organization_id' => $organization->id]);
        $projectAdmin = $this->assignedMember($organization, $project, ProjectRole::Admin);
        $editor = $this->assignedMember($organization, $project, ProjectRole::Editor);
        $target = User::factory()->create();
        $organization->members()->attach($target, ['role' => OrganizationRole::Guest->value]);

        $this->actingAs($editor)
            ->put(route('projects.members.update', [$project, $target]), [
                'role' => ProjectRole::Viewer->value,
            ])
            ->assertForbidden();

        $this->actingAs($projectAdmin)
            ->put(route('projects.members.update', [$project, $target]), [
                'role' => ProjectRole::Viewer->value,
            ])
            ->assertRedirect();

        $this->assertSame(ProjectRole::Viewer, $target->fresh()->roleInProject($project));
    }

    private function assignedMember(
        Organization $organization,
        Project $project,
        ProjectRole $projectRole,
    ): User {
        $user = User::factory()->create();
        $organization->members()->attach($user, ['role' => OrganizationRole::Member->value]);
        $user->assignedProjects()->attach($project, ['role' => $projectRole->value]);

        return $user;
    }
}
