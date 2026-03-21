<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    /**
     * Display the organization switcher.
     */
    public function index(Request $request): Response
    {
        $organizations = $request->user()
            ->organizations()
            ->withCount('projects')
            ->orderBy('name')
            ->get()
            ->map(fn ($org) => [
                'id' => $org->id,
                'name' => $org->name,
                'slug' => $org->slug,
                'role' => $org->pivot->role,
                'projects_count' => $org->projects_count,
            ]);

        $currentOrgId = $request->user()->currentOrganization()?->id;

        return Inertia::render('Organizations/Index', [
            'organizations' => $organizations,
            'currentOrganizationId' => $currentOrgId,
        ]);
    }

    /**
     * Switch to a different organization.
     */
    public function switch(Request $request, Organization $organization): RedirectResponse
    {
        abort_unless($request->user()->belongsToOrganization($organization), 403);

        session(['current_organization_id' => $organization->id]);

        return redirect()->route('dashboard');
    }
}
