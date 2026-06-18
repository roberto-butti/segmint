<?php

namespace App\Http\Controllers;

use App\Enums\OrganizationRole;
use App\Enums\ProjectRole;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use App\Notifications\OrganizationInvitationNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

class OrganizationInvitationController extends Controller
{
    public function store(Request $request, Organization $organization): RedirectResponse
    {
        abort_unless($request->user()->canManageOrganization($organization), 403);

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', Rule::enum(OrganizationRole::class)],
            'project_assignments' => ['array'],
            'project_assignments.*.project_id' => [
                'required',
                'integer',
                Rule::exists('projects', 'id')->where('organization_id', $organization->id),
            ],
            'project_assignments.*.role' => ['required', Rule::enum(ProjectRole::class)],
        ]);

        $email = mb_strtolower($validated['email']);
        $role = OrganizationRole::from($validated['role']);

        abort_if(! $request->user()->isOwnerOf($organization) && $role === OrganizationRole::Admin, 403);

        $alreadyMember = $organization->members()->whereRaw('LOWER(email) = ?', [$email])->exists();
        abort_if($alreadyMember, 422, 'This user already belongs to the organization.');

        $pending = $organization->invitations()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereNull('accepted_at')
            ->whereNull('declined_at')
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->exists();
        abort_if($pending, 422, 'A pending invitation already exists for this email address.');

        $invitation = $organization->invitations()->create([
            'invited_by_id' => $request->user()->id,
            'email' => $email,
            'role' => $role,
            'expires_at' => now()->addDays(7),
        ]);

        if ($role !== OrganizationRole::Admin) {
            $invitation->projects()->sync(
                collect($validated['project_assignments'] ?? [])->mapWithKeys(fn (array $assignment) => [
                    $assignment['project_id'] => ['role' => $assignment['role']],
                ])
            );
        }

        $invitation->load(['organization', 'invitedBy']);
        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if ($user) {
            $user->notify(new OrganizationInvitationNotification($invitation));
        } else {
            Notification::route('mail', $email)->notify(new OrganizationInvitationNotification($invitation));
        }

        return back()->with('success', "Invitation sent to {$email}.");
    }

    public function destroy(Request $request, Organization $organization, OrganizationInvitation $invitation): RedirectResponse
    {
        abort_unless($request->user()->canManageOrganization($organization), 403);
        abort_unless($invitation->organization_id === $organization->id, 404);
        abort_if(! $request->user()->isOwnerOf($organization) && $invitation->role === OrganizationRole::Admin, 403);

        $invitation->update(['revoked_at' => now()]);

        return back()->with('success', "Invitation for {$invitation->email} was revoked.");
    }
}
