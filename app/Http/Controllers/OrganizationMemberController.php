<?php

namespace App\Http\Controllers;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationMemberController extends Controller
{
    public function index(Request $request, Organization $organization): Response
    {
        $this->authorizeManagement($request, $organization);

        $members = $organization->members()
            ->with('assignedProjects:projects.id,public_id,name,organization_id')
            ->orderBy('name')
            ->get()
            ->map(fn (User $member) => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'role' => $member->isOwnerOf($organization)
                    ? 'owner'
                    : $member->pivot->role->value,
                'projects' => $member->assignedProjects
                    ->where('organization_id', $organization->id)
                    ->map->only(['id', 'public_id', 'name'])
                    ->values(),
                'can_manage' => $this->canManageMember($request->user(), $organization, $member),
            ]);

        $invitations = $organization->invitations()
            ->with(['invitedBy:id,name', 'projects:projects.id,public_id,name'])
            ->whereNull('accepted_at')
            ->whereNull('declined_at')
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->get()
            ->map(fn ($invitation) => [
                'id' => $invitation->id,
                'public_id' => $invitation->public_id,
                'email' => $invitation->email,
                'role' => $invitation->role->value,
                'expires_at' => $invitation->expires_at,
                'invited_by' => $invitation->invitedBy->name,
                'projects' => $invitation->projects->map->only(['id', 'public_id', 'name'])->values(),
            ]);

        return Inertia::render('Organizations/Members', [
            'organization' => $this->organizationContext($organization),
            'members' => $members,
            'invitations' => $invitations,
            'projects' => $organization->projects()->orderBy('name')->get(['id', 'public_id', 'name']),
            'roles' => collect(OrganizationRole::cases())
                ->reject(fn (OrganizationRole $role) => ! $request->user()->isOwnerOf($organization) && $role === OrganizationRole::Admin)
                ->map(fn (OrganizationRole $role) => ['value' => $role->value, 'label' => $role->label()])
                ->values(),
        ]);
    }

    public function update(Request $request, Organization $organization, User $member): RedirectResponse
    {
        $this->authorizeMemberManagement($request, $organization, $member);

        $role = OrganizationRole::from($request->validate([
            'role' => ['required', Rule::enum(OrganizationRole::class)],
        ])['role']);

        abort_if(! $request->user()->isOwnerOf($organization) && $role === OrganizationRole::Admin, 403);

        $organization->members()->updateExistingPivot($member->id, ['role' => $role->value]);

        if ($role !== OrganizationRole::Guest) {
            $member->assignedProjects()->where('organization_id', $organization->id)->detach();
        }

        return back()->with('success', "{$member->name}'s organization role was updated.");
    }

    public function syncProjects(Request $request, Organization $organization, User $member): RedirectResponse
    {
        $this->authorizeMemberManagement($request, $organization, $member);
        abort_unless($member->roleInOrganization($organization) === OrganizationRole::Guest, 422);

        $projectIds = $request->validate([
            'project_ids' => ['array'],
            'project_ids.*' => [
                'integer',
                Rule::exists('projects', 'id')->where('organization_id', $organization->id),
            ],
        ])['project_ids'] ?? [];

        $otherProjectIds = $member->assignedProjects()
            ->where('projects.organization_id', '!=', $organization->id)
            ->pluck('projects.id');

        $member->assignedProjects()->sync($otherProjectIds->merge($projectIds));

        return back()->with('success', "{$member->name}'s project access was updated.");
    }

    public function destroy(Request $request, Organization $organization, User $member): RedirectResponse
    {
        $this->authorizeMemberManagement($request, $organization, $member);

        $member->assignedProjects()->where('organization_id', $organization->id)->detach();
        $organization->members()->detach($member);

        return back()->with('success', "{$member->name} was removed from {$organization->name}.");
    }

    private function authorizeManagement(Request $request, Organization $organization): void
    {
        abort_unless($request->user()->canManageOrganization($organization), 403);
    }

    private function authorizeMemberManagement(Request $request, Organization $organization, User $member): void
    {
        $this->authorizeManagement($request, $organization);
        abort_unless($member->belongsToOrganization($organization), 404);
        abort_unless($this->canManageMember($request->user(), $organization, $member), 403);
    }

    private function canManageMember(User $actor, Organization $organization, User $member): bool
    {
        if ($member->isOwnerOf($organization) || $actor->is($member)) {
            return false;
        }

        return $actor->isOwnerOf($organization)
            || $member->roleInOrganization($organization) !== OrganizationRole::Admin;
    }
}
