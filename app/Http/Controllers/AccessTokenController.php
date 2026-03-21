<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccessTokenController extends Controller
{
    /**
     * Display a listing of access tokens for the given project.
     */
    public function index(Request $request, Project $project): Response
    {
        $this->authorize('view', $project);

        $accessTokens = $project->accessTokens()
            ->latest()
            ->get();

        return Inertia::render('AccessTokens/Index', [
            'project' => $project,
            'accessTokens' => $accessTokens,
        ]);
    }
}
