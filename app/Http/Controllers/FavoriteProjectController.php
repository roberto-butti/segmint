<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FavoriteProjectController extends Controller
{
    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('view', $project);

        $request->user()->favoriteProjects()->syncWithoutDetaching([$project->id]);

        return back();
    }

    public function destroy(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('view', $project);

        $request->user()->favoriteProjects()->detach($project);

        return back();
    }
}
