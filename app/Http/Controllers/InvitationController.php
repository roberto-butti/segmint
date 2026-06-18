<?php

namespace App\Http\Controllers;

use App\Enums\OrganizationRole;
use App\Models\OrganizationInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvitationController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Invitations/Index', [
            'invitations' => $this->pendingInvitations($request)
                ->with(['organization:id,public_id,name', 'invitedBy:id,name', 'projects:projects.id,public_id,name'])
                ->latest()
                ->get()
                ->map(fn (OrganizationInvitation $invitation) => [
                    'id' => $invitation->id,
                    'public_id' => $invitation->public_id,
                    'organization' => $invitation->organization,
                    'invited_by' => $invitation->invitedBy->name,
                    'role' => $invitation->role->value,
                    'role_label' => $invitation->role->label(),
                    'projects' => $invitation->projects->map(fn ($project) => [
                        'id' => $project->id,
                        'name' => $project->name,
                        'role' => $project->pivot->role,
                    ])->values(),
                    'expires_at' => $invitation->expires_at,
                ]),
        ]);
    }

    public function show(Request $request, OrganizationInvitation $invitation): RedirectResponse
    {
        $this->authorizeInvitation($request, $invitation);

        return redirect()->route('invitations.index');
    }

    public function accept(Request $request, OrganizationInvitation $invitation): RedirectResponse
    {
        $this->authorizeInvitation($request, $invitation);

        $invitation->organization->members()->syncWithoutDetaching([
            $request->user()->id => ['role' => $invitation->role->value],
        ]);

        if ($invitation->role !== OrganizationRole::Admin) {
            $request->user()->assignedProjects()->syncWithoutDetaching(
                $invitation->projects->mapWithKeys(fn ($project) => [
                    $project->id => ['role' => $project->pivot->role],
                ])
            );
        }

        $invitation->update(['accepted_at' => now()]);
        $this->markNotificationRead($request, $invitation);

        return redirect()->route('organizations.projects.index', $invitation->organization)
            ->with('success', "You joined {$invitation->organization->name}.");
    }

    public function decline(Request $request, OrganizationInvitation $invitation): RedirectResponse
    {
        $this->authorizeInvitation($request, $invitation);
        $invitation->update(['declined_at' => now()]);
        $this->markNotificationRead($request, $invitation);

        return back()->with('success', "Invitation to {$invitation->organization->name} declined.");
    }

    private function authorizeInvitation(Request $request, OrganizationInvitation $invitation): void
    {
        abort_unless(mb_strtolower($request->user()->email) === mb_strtolower($invitation->email), 403);
        abort_unless($invitation->pending, 410);
        abort_if($request->user()->belongsToOrganization($invitation->organization), 422, 'You already belong to this organization.');
    }

    private function pendingInvitations(Request $request)
    {
        return OrganizationInvitation::query()
            ->whereRaw('LOWER(email) = ?', [mb_strtolower($request->user()->email)])
            ->whereNull('accepted_at')
            ->whereNull('declined_at')
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now());
    }

    private function markNotificationRead(Request $request, OrganizationInvitation $invitation): void
    {
        $request->user()->unreadNotifications
            ->filter(fn ($notification) => ($notification->data['invitation_id'] ?? null) === $invitation->id)
            ->each->markAsRead();
    }
}
