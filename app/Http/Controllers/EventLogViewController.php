<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventLogViewController extends Controller
{
    /**
     * Display a paginated listing of event logs for the given project.
     */
    public function index(Request $request, Project $project): Response
    {
        $this->authorize('view', $project);

        $query = $project->eventLogs()->latest();

        // Filter by event type
        if ($request->filled('event_type')) {
            $query->where('event_type', $request->input('event_type'));
        }

        // Filter by visitor ID
        if ($request->filled('visitor_id')) {
            $query->where('visitor_id', $request->input('visitor_id'));
        }

        // Filter by UTM source
        if ($request->filled('utm_source')) {
            $query->where('utm_source', $request->input('utm_source'));
        }

        // Filter by page path
        if ($request->filled('page_path')) {
            $query->where('page_path', 'like', '%'.$request->input('page_path').'%');
        }

        // Search across multiple fields
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('visitor_id', 'like', "%{$search}%")
                    ->orWhere('event_type', 'like', "%{$search}%")
                    ->orWhere('page_path', 'like', "%{$search}%")
                    ->orWhere('page_url', 'like', "%{$search}%")
                    ->orWhere('utm_source', 'like', "%{$search}%")
                    ->orWhere('utm_campaign', 'like', "%{$search}%");
            });
        }

        $eventLogs = $query->paginate(25)->withQueryString();

        // Get distinct event types and UTM sources for filter dropdowns
        $eventTypes = $project->eventLogs()
            ->distinct()
            ->whereNotNull('event_type')
            ->pluck('event_type')
            ->sort()
            ->values();

        $utmSources = $project->eventLogs()
            ->distinct()
            ->whereNotNull('utm_source')
            ->where('utm_source', '!=', '')
            ->pluck('utm_source')
            ->sort()
            ->values();

        return Inertia::render('EventLogs/Index', [
            'project' => $project,
            'eventLogs' => $eventLogs,
            'eventTypes' => $eventTypes,
            'utmSources' => $utmSources,
            'filters' => [
                'search' => $request->input('search', ''),
                'event_type' => $request->input('event_type', ''),
                'visitor_id' => $request->input('visitor_id', ''),
                'utm_source' => $request->input('utm_source', ''),
                'page_path' => $request->input('page_path', ''),
            ],
        ]);
    }
}
