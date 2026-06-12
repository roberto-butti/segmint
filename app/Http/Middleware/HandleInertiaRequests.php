<?php

namespace App\Http\Middleware;

use App\Models\Organization;
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
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'segmentCopy' => fn () => $request->session()->get('segmentCopy'),
                'ruleTemplateCopy' => fn () => $request->session()->get('ruleTemplateCopy'),
            ],
            'navigationContext' => $navigationContext,
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * @return array{
     *     organizations: array<int, array{id: int, public_id: string, name: string}>,
     *     organization: array{id: int, public_id: string, name: string}|null,
     *     projects: array<int, array{id: int, public_id: string, name: string}>,
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

        return [
            'organizations' => $organizations,
            'organization' => $organization ? $this->navigationItem($organization) : null,
            'projects' => $organization
                ? $organization->projects()
                    ->orderBy('name')
                    ->get(['id', 'public_id', 'name'])
                    ->map(fn (Project $item) => $this->navigationItem($item))
                    ->values()
                    ->all()
                : [],
            'project' => $project ? $this->navigationItem($project) : null,
        ];
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
