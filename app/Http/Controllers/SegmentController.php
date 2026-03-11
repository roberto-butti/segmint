<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SegmentController extends Controller
{
    /**
     * Display a listing of segments for the given project.
     */
    public function index(Request $request, Project $project): Response
    {
        abort_unless($project->user_id === $request->user()->id, 403);

        $segments = $project->segments()
            ->latest()
            ->get();

        return Inertia::render('Segments/Index', [
            'project' => $project,
            'segments' => $segments,
        ]);
    }
}
