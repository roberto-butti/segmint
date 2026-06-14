<?php

namespace App\Http\Controllers;

use App\Models\AccessToken;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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

        if ($request->session()->has('accessTokenSecret')) {
            Inertia::clearHistory();
        }

        $accessTokens = $project->accessTokens()
            ->latest()
            ->get()
            ->map(fn (AccessToken $accessToken) => [
                'id' => $accessToken->id,
                'name' => $accessToken->name,
                'preview' => $accessToken->preview(),
                'active' => $accessToken->active,
                'last_used_at' => $accessToken->last_used_at,
                'created_at' => $accessToken->created_at,
            ]);

        return Inertia::render('AccessTokens/Index', [
            'project' => $project,
            'organization' => $this->organizationContext($project->organization),
            'accessTokens' => $accessTokens,
            'canManageProject' => $request->user()->can('update', $project),
        ]);
    }

    /**
     * Create a token and reveal its secret once.
     */
    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('access_tokens')->where('project_id', $project->id),
            ],
        ]);

        $token = AccessToken::generateToken();
        $accessToken = $project->accessTokens()->create([
            'name' => $validated['name'],
            'token' => $token,
            'active' => true,
        ]);

        return $this->redirectWithSecret($project, $accessToken, $token, 'created');
    }

    /**
     * Revoke or reactivate a token.
     */
    public function update(Request $request, Project $project, AccessToken $accessToken): RedirectResponse
    {
        $this->authorize('update', $project);
        $this->ensureBelongsToProject($project, $accessToken);

        $validated = $request->validate([
            'active' => ['required', 'boolean:strict'],
        ]);

        $accessToken->update(['active' => $validated['active']]);

        return redirect()->route('projects.access-tokens.index', $project)
            ->with('success', $validated['active'] ? 'Access token reactivated.' : 'Access token revoked.');
    }

    /**
     * Replace a token secret and reveal the new value once.
     */
    public function rotate(Request $request, Project $project, AccessToken $accessToken): RedirectResponse
    {
        $this->authorize('update', $project);
        $this->ensureBelongsToProject($project, $accessToken);

        $token = AccessToken::generateToken();
        $accessToken->update(['token' => $token]);

        return $this->redirectWithSecret($project, $accessToken, $token, 'rotated');
    }

    private function ensureBelongsToProject(Project $project, AccessToken $accessToken): void
    {
        abort_unless($accessToken->project_id === $project->id, 404);
    }

    private function redirectWithSecret(
        Project $project,
        AccessToken $accessToken,
        string $token,
        string $action,
    ): RedirectResponse {
        return redirect()->route('projects.access-tokens.index', $project)
            ->with('accessTokenSecret', [
                'id' => Str::uuid()->toString(),
                'access_token_id' => $accessToken->id,
                'name' => $accessToken->name,
                'token' => $token,
                'action' => $action,
            ]);
    }
}
