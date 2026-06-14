<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\Project;
use App\Models\User;
use App\Notifications\OrganizationInvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OrganizationMemberManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_manage_members_and_guest_project_access(): void
    {
        ['user' => $owner, 'organization' => $organization] = $this->createUserWithOrganization();
        $guest = User::factory()->create();
        $organization->members()->attach($guest, ['role' => OrganizationRole::Guest->value]);
        $project = Project::factory()->create(['organization_id' => $organization->id]);
        $otherProject = Project::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($owner)
            ->get(route('organizations.members.index', $organization))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Organizations/Members')
                ->has('members', 2)
                ->has('projects', 2)
            );

        $this->actingAs($owner)
            ->put(route('organizations.members.projects.update', [$organization, $guest]), [
                'project_ids' => [$project->id],
            ])
            ->assertRedirect();

        $this->assertTrue($guest->fresh()->assignedProjects->contains($project));
        $this->assertFalse($guest->fresh()->assignedProjects->contains($otherProject));

        $this->actingAs($owner)
            ->patch(route('organizations.members.update', [$organization, $guest]), [
                'role' => OrganizationRole::Member->value,
            ])
            ->assertRedirect();

        $this->assertSame(OrganizationRole::Member, $guest->roleInOrganization($organization));
        $this->assertCount(0, $guest->fresh()->assignedProjects);
    }

    public function test_admin_cannot_manage_owner_or_another_admin(): void
    {
        ['user' => $owner, 'organization' => $organization] = $this->createUserWithOrganization();
        $admin = User::factory()->create();
        $otherAdmin = User::factory()->create();
        $member = User::factory()->create();
        $organization->members()->attach($admin, ['role' => OrganizationRole::Admin->value]);
        $organization->members()->attach($otherAdmin, ['role' => OrganizationRole::Admin->value]);
        $organization->members()->attach($member, ['role' => OrganizationRole::Member->value]);

        $this->actingAs($admin)
            ->patch(route('organizations.members.update', [$organization, $owner]), ['role' => 'guest'])
            ->assertForbidden();
        $this->actingAs($admin)
            ->delete(route('organizations.members.destroy', [$organization, $otherAdmin]))
            ->assertForbidden();
        $this->actingAs($admin)
            ->patch(route('organizations.members.update', [$organization, $member]), ['role' => 'guest'])
            ->assertRedirect();
    }

    public function test_guest_can_only_access_assigned_projects(): void
    {
        $organization = Organization::factory()->create();
        $guest = User::factory()->create();
        $organization->members()->attach($guest, ['role' => OrganizationRole::Guest->value]);
        $assigned = Project::factory()->create(['organization_id' => $organization->id]);
        $unassigned = Project::factory()->create(['organization_id' => $organization->id]);
        $guest->assignedProjects()->attach($assigned);

        $this->actingAs($guest)
            ->get(route('organizations.projects.index', $organization))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('projects', 1)
                ->where('projects.0.id', $assigned->id)
            );

        $this->actingAs($guest)->get(route('projects.show', $assigned))->assertOk();
        $this->actingAs($guest)->get(route('projects.show', $unassigned))->assertForbidden();
        $this->actingAs($guest)
            ->get(route('organizations.dashboard', $organization))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('limitedGuestView', true)
                ->has('projects', 1)
                ->where('projects.0.id', $assigned->id)
            );
    }

    public function test_owner_can_invite_existing_user_as_guest_with_projects_and_user_can_accept(): void
    {
        Notification::fake();
        ['user' => $owner, 'organization' => $organization] = $this->createUserWithOrganization();
        $invitee = User::factory()->create();
        $project = Project::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($owner)
            ->post(route('organizations.invitations.store', $organization), [
                'email' => $invitee->email,
                'role' => OrganizationRole::Guest->value,
                'project_ids' => [$project->id],
            ])
            ->assertRedirect();

        $invitation = OrganizationInvitation::firstOrFail();
        Notification::assertSentTo($invitee, OrganizationInvitationNotification::class);

        $this->actingAs($invitee)
            ->get(route('invitations.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('invitations', 1)
                ->where('invitations.0.organization.id', $organization->id)
            );

        $this->actingAs($invitee)
            ->post(route('invitations.accept', $invitation))
            ->assertRedirect(route('organizations.projects.index', $organization));

        $this->assertSame(OrganizationRole::Guest, $invitee->roleInOrganization($organization));
        $this->assertTrue($invitee->fresh()->assignedProjects->contains($project));
        $this->assertNotNull($invitation->fresh()->accepted_at);
    }

    public function test_admin_cannot_invite_or_revoke_an_admin_invitation(): void
    {
        ['user' => $owner, 'organization' => $organization] = $this->createUserWithOrganization();
        $admin = User::factory()->create();
        $organization->members()->attach($admin, ['role' => OrganizationRole::Admin->value]);

        $this->actingAs($admin)
            ->post(route('organizations.invitations.store', $organization), [
                'email' => 'new-admin@example.com',
                'role' => OrganizationRole::Admin->value,
            ])
            ->assertForbidden();

        $invitation = $organization->invitations()->create([
            'invited_by_id' => $owner->id,
            'email' => 'another-admin@example.com',
            'role' => OrganizationRole::Admin,
            'expires_at' => now()->addDay(),
        ]);

        $this->actingAs($admin)
            ->delete(route('organizations.invitations.destroy', [$organization, $invitation]))
            ->assertForbidden();
    }

    public function test_invitation_can_only_be_accepted_by_matching_email(): void
    {
        ['user' => $owner, 'organization' => $organization] = $this->createUserWithOrganization();
        $invitee = User::factory()->create();
        $otherUser = User::factory()->create();
        $invitation = $organization->invitations()->create([
            'invited_by_id' => $owner->id,
            'email' => $invitee->email,
            'role' => OrganizationRole::Member,
            'expires_at' => now()->addDay(),
        ]);

        $this->actingAs($otherUser)
            ->post(route('invitations.accept', $invitation))
            ->assertForbidden();
    }
}
