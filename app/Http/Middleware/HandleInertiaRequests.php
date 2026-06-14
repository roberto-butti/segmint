<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $navigationContext = fn () => $this->navigationContext($request);

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
                'pendingInvitationCount' => fn () => $request->user()
                    ? OrganizationInvitation::query()
                        ->whereRaw('LOWER(email) = ?', [mb_strtolower($request->user()->email)])
                        ->whereNull('accepted_at')
                        ->whereNull('declined_at')
                        ->whereNull('revoked_at')
                        ->where('expires_at', '>', now())
                        ->count()
                    : 0,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'segmentCopy' => fn () => $request->session()->get('segmentCopy'),
                'ruleTemplateCopy' => fn () => $request->session()->get('ruleTemplateCopy'),
                'accessTokenSecret' => fn () => $request->session()->get('accessTokenSecret'),
            ],
            'navigationContext' => $navigationContext,
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * @return array{
     *     organizations: array<int, array{id: int, public_id: string, name: string}>,
     *     organization: array{id: int, public_id: string, name: string}|null,
     *     canViewOrganizationDashboard: bool,
     *     canManageOrganization: bool,
     *     projects: array<int, array{id: int, public_id: string, name: string, is_favorite: bool}>,
     *     project: array{id: int, public_id: string, name: string}|null
     * }
     */
    private function navigationContext(Request $request): array
    {
        $user = $request->user();

        if (! $user) {
            return [
                'organizations' => [],
                'organization' => null,
                'canViewOrganizationDashboard' => false,
                'canManageOrganization' => false,
                'projects' => [],
                'project' => null,
            ];
        }

        $routeOrganization = $request->route('organization');
        $routeProject = $request->route('project');
        $project = $routeProject instanceof Project ? $routeProject : null;
        $organization = $routeOrganization instanceof Organization
            ? $routeOrganization
            : $project?->organization;

        $organizations = $user->organizations()
            ->orderBy('name')
            ->get(['organizations.id', 'organizations.public_id', 'organizations.name'])
            ->map(fn (Organization $item) => $this->navigationItem($item))
            ->values()
            ->all();

        $projects = $organization
            ? $this->projectNavigationItems($request, $organization)
            : [];

        return [
            'organizations' => $organizations,
            'organization' => $organization ? $this->navigationItem($organization) : null,
            'canViewOrganizationDashboard' => $organization
                ? $user->belongsToOrganization($organization)
                : false,
            'canManageOrganization' => $organization
                ? $user->canManageOrganization($organization)
                : false,
            'projects' => $projects,
            'project' => $project ? $this->navigationItem($project) : null,
        ];
    }

    /**
     * @return array<int, array{id: int, public_id: string, name: string, is_favorite: bool}>
     */
    private function projectNavigationItems(Request $request, Organization $organization): array
    {
        $favoriteProjectIds = $request->user()
            ->favoriteProjects()
            ->where('organization_id', $organization->id)
            ->pluck('projects.id');

        $projects = $request->user()->roleInOrganization($organization)?->canAccessAllProjects() === true
            ? $organization->projects()
            : $request->user()->assignedProjects()->where('organization_id', $organization->id);

        return $projects
            ->orderBy('name')
            ->get(['projects.id', 'projects.public_id', 'projects.name'])
            ->map(fn (Project $project) => [
                ...$this->navigationItem($project),
                'is_favorite' => $favoriteProjectIds->contains($project->id),
            ])
            ->sortBy(fn (array $project) => [
                $project['is_favorite'] ? 0 : 1,
                mb_strtolower($project['name']),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{id: int, public_id: string, name: string}
     */
    private function navigationItem(Organization|Project $item): array
    {
        return [
            'id' => $item->id,
            'public_id' => $item->public_id,
            'name' => $item->name,
        ];
    }
}
