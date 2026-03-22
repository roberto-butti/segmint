<?php

namespace App\Http\Controllers;

use App\Enums\OrganizationRole;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Organization;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource, filtered by organization.
     */
    public function index(Request $request): Response
    {
        $userOrgs = $request->user()
            ->organizations()
            ->orderBy('name')
            ->get();

        $ownedOrgId = $request->user()->owned_organization_id;

        // Sort: owned org first, then alphabetically
        $orgOptions = $userOrgs
            ->sortBy(fn ($org) => [
                $org->id === $ownedOrgId ? 0 : 1,
                $org->name,
            ])
            ->values()
            ->map(fn ($org) => [
                'id' => $org->id,
                'name' => $org->name,
                'role' => $org->id === $ownedOrgId ? 'owner' : $org->pivot->role,
                'isOwned' => $org->id === $ownedOrgId,
            ]);

        // Determine selected org: query param > session > owned org > null
        $selectedOrgId = $request->input('organization_id');

        if ($selectedOrgId) {
            session(['projects_organization_id' => (int) $selectedOrgId]);
        } else {
            $selectedOrgId = session('projects_organization_id');
        }

        // Validate the session value still belongs to the user
        if ($selectedOrgId && ! $userOrgs->contains('id', (int) $selectedOrgId)) {
            $selectedOrgId = null;
            session()->forget('projects_organization_id');
        }

        // Default to owned org
        if (! $selectedOrgId && $ownedOrgId) {
            $selectedOrgId = $ownedOrgId;
            session(['projects_organization_id' => $selectedOrgId]);
        }

        $projects = collect();
        $selectedOrg = null;

        if ($selectedOrgId) {
            $selectedOrg = $userOrgs->firstWhere('id', (int) $selectedOrgId);
            if ($selectedOrg) {
                $projects = $selectedOrg->projects()->latest()->get();
            }
        }

        return Inertia::render('Projects/Index', [
            'organizations' => $orgOptions,
            'selectedOrganizationId' => $selectedOrgId ? (int) $selectedOrgId : null,
            'selectedOrganizationRole' => $selectedOrg?->pivot->role,
            'projects' => $projects,
        ]);
    }

    /**
     * Show the form for creating a new project.
     */
    public function create(Request $request): Response
    {
        $organizations = $request->user()
            ->organizations()
            ->get()
            ->filter(fn ($org) => OrganizationRole::from($org->pivot->role)->canManageProjects())
            ->map(fn ($org) => [
                'id' => $org->id,
                'name' => $org->name,
            ])
            ->values();

        return Inertia::render('Projects/Create', [
            'organizations' => $organizations,
        ]);
    }

    /**
     * Store a newly created project.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
        ]);

        $organization = Organization::findOrFail($validated['organization_id']);
        $role = $request->user()->roleInOrganization($organization);

        abort_unless($role !== null && $role->canManageProjects(), 403);

        $project = $organization->projects()->create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'active' => true,
        ]);

        return redirect()->route('projects.show', $project);
    }

    /**
     * Display the specified project.
     */
    public function show(Request $request, Project $project): Response
    {
        $this->authorize('view', $project);

        $thirtyDaysAgo = Carbon::now()->subDays(30)->startOfDay();

        $eventsOverTime = $project->eventLogs()
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date')
            ->toArray();

        $oneHourAgo = Carbon::now()->subHour();

        $eventsLastHour = $project->eventLogs()
            ->where('created_at', '>=', $oneHourAgo)
            ->orderBy('created_at')
            ->get()
            ->groupBy(fn ($log) => Carbon::parse($log->created_at)->format('H:i'))
            ->map(fn ($group) => $group->count())
            ->toArray();

        $segmentMatchesRaw = DB::table('segment_matches')
            ->join('segments', 'segment_matches.segment_id', '=', 'segments.id')
            ->where('segments.project_id', $project->id)
            ->where('segment_matches.matched', true)
            ->where('segment_matches.created_at', '>=', $oneHourAgo)
            ->orderBy('segment_matches.created_at')
            ->select('segment_matches.created_at', 'segments.name as segment_name')
            ->get();

        $segmentMatchMinutes = $segmentMatchesRaw
            ->map(fn ($row) => Carbon::parse($row->created_at)->format('H:i'))
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        $segmentMatchesLastHour = $segmentMatchesRaw
            ->groupBy('segment_name')
            ->map(function ($rows) use ($segmentMatchMinutes) {
                $byMinute = $rows->groupBy(fn ($row) => Carbon::parse($row->created_at)->format('H:i'))
                    ->map(fn ($group) => $group->count());

                return collect($segmentMatchMinutes)
                    ->map(fn ($minute) => $byMinute->get($minute, 0))
                    ->values()
                    ->toArray();
            })
            ->toArray();

        $eventsByType = $project->eventLogs()
            ->select('event_type', DB::raw('COUNT(*) as count'))
            ->groupBy('event_type')
            ->orderByDesc('count')
            ->get()
            ->pluck('count', 'event_type')
            ->toArray();

        $segmentMatchQuery = DB::table('segment_matches')
            ->join('segments', 'segment_matches.segment_id', '=', 'segments.id')
            ->where('segments.project_id', $project->id)
            ->where('segment_matches.matched', true)
            ->select('segments.name as segment_name', DB::raw('COUNT(*) as count'))
            ->groupBy('segments.name')
            ->orderByDesc('count');

        $segmentDistribution = (clone $segmentMatchQuery)
            ->get()
            ->pluck('count', 'segment_name')
            ->toArray();

        $topSegments = (clone $segmentMatchQuery)
            ->limit(10)
            ->get()
            ->pluck('count', 'segment_name')
            ->toArray();

        return Inertia::render('Projects/Show', [
            'project' => $project,
            'segmentsCount' => $project->segments()->count(),
            'activeSegmentsCount' => $project->segments()->where('active', true)->count(),
            'eventLogsCount' => $project->eventLogs()->count(),
            'accessTokensCount' => $project->accessTokens()->count(),
            'ruleTemplatesCount' => $project->ruleTemplates()->count(),
            'eventsOverTime' => $eventsOverTime,
            'eventsLastHour' => $eventsLastHour,
            'segmentMatchesLastHour' => [
                'labels' => $segmentMatchMinutes,
                'datasets' => $segmentMatchesLastHour,
            ],
            'eventsByType' => $eventsByType,
            'segmentDistribution' => $segmentDistribution,
            'topSegments' => $topSegments,
        ]);
    }

    /**
     * Show the form for editing the specified project.
     */
    public function edit(Request $request, Project $project): Response
    {
        $this->authorize('update', $project);

        return Inertia::render('Projects/Edit', [
            'project' => $project,
        ]);
    }

    /**
     * Update the specified project in storage.
     */
    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        $project->update($request->validated());

        return redirect()->route('projects.show', $project)
            ->with('success', 'Project updated successfully.');
    }
}
