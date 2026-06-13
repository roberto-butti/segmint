<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProjectRequest;
use App\Models\Organization;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    /**
     * Redirect to the user's current organization project collection.
     */
    public function redirectIndex(Request $request): RedirectResponse
    {
        $organizations = $request->user()->organizations()->orderBy('name')->get();
        $organization = $organizations->firstWhere('id', session('projects_organization_id'))
            ?? $organizations->firstWhere('id', $request->user()->owned_organization_id)
            ?? $organizations->first();

        if (! $organization) {
            return redirect()->route('dashboard');
        }

        return redirect()->route('organizations.projects.index', $organization);
    }

    /**
     * Display the projects belonging to an organization.
     */
    public function index(Request $request, Organization $organization): Response
    {
        abort_unless($request->user()->belongsToOrganization($organization), 403);

        session(['projects_organization_id' => $organization->id]);

        $role = $request->user()->roleInOrganization($organization);
        $favoriteProjectIds = $request->user()
            ->favoriteProjects()
            ->where('organization_id', $organization->id)
            ->pluck('projects.id');

        return Inertia::render('Projects/Index', [
            'organization' => $this->organizationContext($organization),
            'canManageProjects' => $role?->canManageProjects() ?? false,
            'projects' => $organization->projects()
                ->latest()
                ->get()
                ->map(fn (Project $project) => [
                    ...$project->toArray(),
                    'is_favorite' => $favoriteProjectIds->contains($project->id),
                ]),
        ]);
    }

    /**
     * Show the form for creating a new project.
     */
    public function create(Request $request, Organization $organization): Response
    {
        $role = $request->user()->roleInOrganization($organization);
        abort_unless($role !== null && $role->canManageProjects(), 403);

        return Inertia::render('Projects/Create', [
            'organization' => $organization,
        ]);
    }

    /**
     * Store a newly created project.
     */
    public function store(Request $request, Organization $organization): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $role = $request->user()->roleInOrganization($organization);

        abort_unless($role !== null && $role->canManageProjects(), 403);

        $project = $organization->projects()->create([
            'name' => $validated['name'],
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
            'organization' => $this->organizationContext($project->organization),
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
            'organization' => $this->organizationContext($project->organization),
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
