<?php

namespace App\Http\Controllers;

use App\Enums\OrganizationRole;
use App\Models\EventLog;
use App\Models\Organization;
use App\Models\Project;
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
            ->map(function (Organization $organization) use ($user) {
                $role = $organization->pivot->role;
                $assignedProjects = ! $user->isOwnerOf($organization)
                    && $role?->canAccessAllProjects() !== true
                    ? $user->assignedProjects()->where('projects.organization_id', $organization->id)
                    : null;

                return [
                    'id' => $organization->id,
                    'public_id' => $organization->public_id,
                    'name' => $organization->name,
                    'role' => $user->isOwnerOf($organization) ? 'owner' : $role->value,
                    'projects_count' => $assignedProjects?->count() ?? $organization->projects_count,
                    'active_projects_count' => $assignedProjects?->where('active', true)->count() ?? $organization->active_projects_count,
                    'members_count' => $organization->members_count,
                ];
            });

        $accessibleProjectIds = $user->accessibleProjects()->select('projects.id');
        $favoriteProjects = $user->favoriteProjects()
            ->whereIn('projects.id', $accessibleProjectIds)
            ->with('organization:id,name')
            ->orderBy('projects.name')
            ->get()
            ->map(fn (Project $project) => [
                'id' => $project->id,
                'public_id' => $project->public_id,
                'name' => $project->name,
                'organization_name' => $project->organization->name,
            ]);

        $recentlyActiveProjects = EventLog::query()
            ->join('projects', 'event_logs.project_id', '=', 'projects.id')
            ->join('organizations', 'projects.organization_id', '=', 'organizations.id')
            ->whereIn('projects.id', $user->accessibleProjects()->select('projects.id'))
            ->select([
                'projects.id',
                'projects.public_id',
                'projects.name',
                'organizations.name as organization_name',
                DB::raw('MAX(event_logs.created_at) as last_event_at'),
                DB::raw("SUM(CASE WHEN event_logs.created_at >= '".now()->subDay()->toDateTimeString()."' THEN 1 ELSE 0 END) as events_count_24h"),
            ])
            ->groupBy('projects.id', 'projects.public_id', 'projects.name', 'organizations.name')
            ->orderByRaw('MAX(event_logs.created_at) DESC')
            ->limit(5)
            ->get()
            ->map(fn ($project) => [
                'id' => $project->id,
                'public_id' => $project->public_id,
                'name' => $project->name,
                'organization_name' => $project->organization_name,
                'last_event_at' => Carbon::parse($project->last_event_at)->toIso8601String(),
                'events_count_24h' => (int) $project->events_count_24h,
            ]);

        return Inertia::render('Dashboard', [
            'organizations' => $organizations,
            'favoriteProjects' => $favoriteProjects,
            'recentlyActiveProjects' => $recentlyActiveProjects,
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
        $hasLimitedProjectAccess = ! $user->isOwnerOf($organization)
            && $role?->canAccessAllProjects() !== true;
        $projectsQuery = $hasLimitedProjectAccess
            ? $user->assignedProjects()->where('projects.organization_id', $organization->id)
            : $organization->projects();

        $projects = $projectsQuery
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

        if ($hasLimitedProjectAccess) {
            return Inertia::render('Organizations/Dashboard', [
                'organization' => [
                    'id' => $organization->id,
                    'public_id' => $organization->public_id,
                    'name' => $organization->name,
                ],
                'currentUserRole' => [
                    'value' => $role->value,
                    'label' => $role->label(),
                ],
                'canManageProjects' => false,
                'canManageOrganization' => false,
                'limitedProjectView' => true,
                'stats' => [
                    'members_count' => 0,
                    'projects_count' => $projects->count(),
                    'active_projects_count' => $projects->where('active', true)->count(),
                    'segments_count' => 0,
                    'active_segments_count' => 0,
                    'events_count' => 0,
                    'unique_visitors_count' => 0,
                ],
                'eventsOverTime' => [],
                'roleCounts' => [],
                'projects' => $projects,
            ]);
        }

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
            'canManageProjects' => $user->isOwnerOf($organization)
                || ($role?->canManageProjects() ?? false),
            'canManageOrganization' => $user->canManageOrganization($organization),
            'limitedProjectView' => false,
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
