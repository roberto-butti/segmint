<?php

namespace App\Http\Controllers;

use App\Enums\OrganizationRole;
use App\Models\EventLog;
use App\Models\Organization;
use App\Models\Segment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the organizations available to the user.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $organizations = $user->organizations()
            ->withCount([
                'projects',
                'projects as active_projects_count' => fn ($query) => $query->where('active', true),
                'members',
            ])
            ->orderBy('name')
            ->get()
            ->sortBy(fn (Organization $organization) => [
                $user->isOwnerOf($organization) ? 0 : 1,
                $organization->name,
            ])
            ->values()
            ->map(fn (Organization $organization) => [
                'id' => $organization->id,
                'public_id' => $organization->public_id,
                'name' => $organization->name,
                'role' => $user->isOwnerOf($organization)
                    ? 'owner'
                    : $organization->pivot->role->value,
                'projects_count' => $organization->projects_count,
                'active_projects_count' => $organization->active_projects_count,
                'members_count' => $organization->members_count,
            ]);

        return Inertia::render('Dashboard', [
            'organizations' => $organizations,
        ]);
    }

    /**
     * Display an overview of a specific organization.
     */
    public function show(Request $request, Organization $organization): Response
    {
        $user = $request->user();
        abort_unless($user->belongsToOrganization($organization), 403);

        session(['projects_organization_id' => $organization->id]);

        $role = $user->roleInOrganization($organization);
        $projectIds = $organization->projects()->select('id');
        $thirtyDaysAgo = Carbon::now()->subDays(30)->startOfDay();

        $eventsOverTime = EventLog::query()
            ->whereIn('project_id', clone $projectIds)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date')
            ->toArray();

        $projects = $organization->projects()
            ->withCount(['segments', 'eventLogs'])
            ->latest()
            ->get()
            ->map(fn ($project) => [
                'id' => $project->id,
                'public_id' => $project->public_id,
                'name' => $project->name,
                'description' => $project->description,
                'active' => $project->active,
                'segments_count' => $project->segments_count,
                'event_logs_count' => $project->event_logs_count,
            ]);

        $roleCounts = $organization->members()
            ->select('organization_memberships.role', DB::raw('COUNT(*) as count'))
            ->groupBy('organization_memberships.role')
            ->pluck('count', 'role')
            ->mapWithKeys(fn ($count, $role) => [
                OrganizationRole::from($role)->label() => $count,
            ])
            ->toArray();

        return Inertia::render('Organizations/Dashboard', [
            'organization' => [
                'id' => $organization->id,
                'public_id' => $organization->public_id,
                'name' => $organization->name,
            ],
            'currentUserRole' => [
                'value' => $user->isOwnerOf($organization) ? 'owner' : $role?->value,
                'label' => $user->isOwnerOf($organization) ? 'Owner' : $role?->label(),
            ],
            'canManageProjects' => $role?->canManageProjects() ?? false,
            'stats' => [
                'members_count' => $organization->members()->count(),
                'projects_count' => $organization->projects()->count(),
                'active_projects_count' => $organization->projects()->where('active', true)->count(),
                'segments_count' => Segment::whereIn('project_id', clone $projectIds)->count(),
                'active_segments_count' => Segment::whereIn('project_id', clone $projectIds)->where('active', true)->count(),
                'events_count' => EventLog::whereIn('project_id', clone $projectIds)->count(),
                'unique_visitors_count' => EventLog::whereIn('project_id', clone $projectIds)->distinct('visitor_id')->count('visitor_id'),
            ],
            'eventsOverTime' => $eventsOverTime,
            'roleCounts' => $roleCounts,
            'projects' => $projects,
        ]);
    }
}
