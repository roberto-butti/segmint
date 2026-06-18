<?php

namespace App\Http\Controllers;

use App\Enums\OrganizationRole;
use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProjectMemberController extends Controller
{
    public function index(Request $request, Project $project): Response
    {
        $this->authorize('manage', $project);

        $organization = $project->organization;
        $explicitAssignments = $project->members()
            ->withPivot('role')
            ->get()
            ->keyBy('id');

        $members = $organization->members()
            ->orderBy('name')
            ->get()
            ->map(function (User $member) use ($organization, $explicitAssignments) {
                $organizationRole = $member->isOwnerOf($organization)
                    ? 'owner'
                    : $member->pivot->role->value;
                $implicit = $member->isOwnerOf($organization)
                    || $member->pivot->role === OrganizationRole::Admin;
                $assignment = $explicitAssignments->get($member->id);

                return [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                    'organization_role' => $organizationRole,
                    'project_role' => $implicit
                        ? ProjectRole::Admin->value
                        : $assignment?->pivot->role->value,
                    'implicit' => $implicit,
                ];
            });

        return Inertia::render('Projects/Members', [
            'project' => $project,
            'organization' => $this->organizationContext($organization),
            'members' => $members,
            'projectRoles' => collect(ProjectRole::cases())
                ->map(fn (ProjectRole $role) => ['value' => $role->value, 'label' => $role->label()])
                ->values(),
        ]);
    }

    public function update(Request $request, Project $project, User $member): RedirectResponse
    {
        $this->authorize('manage', $project);
        abort_unless($member->belongsToOrganization($project->organization), 404);
        abort_if($member->isOwnerOf($project->organization)
            || $member->roleInOrganization($project->organization) === OrganizationRole::Admin, 422);

        $role = ProjectRole::from($request->validate([
            'role' => ['required', Rule::enum(ProjectRole::class)],
        ])['role']);

        $project->members()->syncWithoutDetaching([
            $member->id => ['role' => $role->value],
        ]);

        return back()->with('success', "{$member->name}'s project role was updated.");
    }

    public function destroy(Request $request, Project $project, User $member): RedirectResponse
    {
        $this->authorize('manage', $project);
        abort_unless($member->belongsToOrganization($project->organization), 404);
        abort_if($member->isOwnerOf($project->organization)
            || $member->roleInOrganization($project->organization) === OrganizationRole::Admin, 422);

        $project->members()->detach($member);

        return back()->with('success', "{$member->name}'s project access was removed.");
    }
}
