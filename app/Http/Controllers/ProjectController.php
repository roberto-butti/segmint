<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProjectRequest;
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
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $projects = $request->user()
            ->projects()
            ->latest()
            ->get();

        return Inertia::render('Projects/Index', [
            'projects' => $projects,
        ]);
    }

    /**
     * Display the specified project.
     */
    public function show(Request $request, Project $project): Response
    {
        abort_unless($project->user_id === $request->user()->id, 403);

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
        abort_unless($project->user_id === $request->user()->id, 403);

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
