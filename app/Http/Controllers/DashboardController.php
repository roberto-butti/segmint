<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $ownedOrg = $user->ownedOrganization;

        return Inertia::render('Dashboard', [
            'projectsCount' => $user->accessibleProjects()->count(),
            'organizationsCount' => $user->organizations()->count(),
            'ownedOrganization' => $ownedOrg ? [
                'id' => $ownedOrg->id,
                'name' => $ownedOrg->name,
                'projectsCount' => $ownedOrg->projects()->count(),
            ] : null,
        ]);
    }
}
