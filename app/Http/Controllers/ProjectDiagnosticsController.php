<?php

namespace App\Http\Controllers;

use App\Models\DiagnosticScenario;
use App\Models\EventLog;
use App\Models\Project;
use App\Services\SegmentEngine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProjectDiagnosticsController extends Controller
{
    public function index(Request $request, Project $project): Response
    {
        $this->authorize('view', $project);

        return $this->render($project, $this->defaultPayload($request), null, null);
    }

    public function evaluate(Request $request, Project $project, SegmentEngine $engine): Response
    {
        $this->authorize('view', $project);

        $payload = $this->validatedPayload($request);
        $diagnostics = $this->diagnosePayload($request, $project, $engine, $payload);

        return $this->render($project, $payload, $diagnostics, now()->toIso8601String());
    }

    public function store(Request $request, Project $project, SegmentEngine $engine): Response
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('diagnostic_scenarios', 'name')->where('project_id', $project->id),
            ],
            'payload' => ['required', 'array'],
            ...$this->payloadRules('payload.'),
        ]);

        $payload = $this->normalizePayload($request, $validated['payload']);
        $diagnostics = $this->diagnosePayload($request, $project, $engine, $payload);

        $evaluatedAt = now();

        $project->diagnosticScenarios()->create([
            'name' => $validated['name'],
            'payload' => $payload,
            'last_result' => $diagnostics,
            'last_run_at' => $evaluatedAt,
        ]);

        return $this->render($project, $payload, $diagnostics, $evaluatedAt->toIso8601String());
    }

    public function run(Request $request, Project $project, DiagnosticScenario $diagnosticScenario, SegmentEngine $engine): Response
    {
        $this->authorize('update', $project);
        $this->ensureScenarioBelongsToProject($project, $diagnosticScenario);

        $payload = $diagnosticScenario->payload;
        $diagnostics = $this->diagnosePayload($request, $project, $engine, $payload);

        $evaluatedAt = now();

        $diagnosticScenario->update([
            'last_result' => $diagnostics,
            'last_run_at' => $evaluatedAt,
        ]);

        return $this->render($project, $payload, $diagnostics, $evaluatedAt->toIso8601String());
    }

    public function update(Request $request, Project $project, DiagnosticScenario $diagnosticScenario, SegmentEngine $engine): Response
    {
        $this->authorize('update', $project);
        $this->ensureScenarioBelongsToProject($project, $diagnosticScenario);

        $validated = $request->validate([
            'payload' => ['required', 'array'],
            ...$this->payloadRules('payload.'),
        ]);

        $payload = $this->normalizePayload($request, $validated['payload']);
        $diagnostics = $this->diagnosePayload($request, $project, $engine, $payload);
        $evaluatedAt = now();

        $diagnosticScenario->update([
            'payload' => $payload,
            'last_result' => $diagnostics,
            'last_run_at' => $evaluatedAt,
        ]);

        return $this->render($project, $payload, $diagnostics, $evaluatedAt->toIso8601String());
    }

    public function destroy(Request $request, Project $project, DiagnosticScenario $diagnosticScenario): RedirectResponse
    {
        $this->authorize('update', $project);
        $this->ensureScenarioBelongsToProject($project, $diagnosticScenario);

        $diagnosticScenario->delete();

        return redirect()->route('projects.diagnostics.index', $project);
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultPayload(Request $request): array
    {
        return [
            'visitor_id' => 'visitor-1',
            'type' => 'page-view',
            'url' => 'https://example.com/pricing',
            'referrer' => '',
            'utms' => [
                'utm_source' => '',
                'utm_medium' => '',
                'utm_campaign' => '',
                'utm_term' => '',
                'utm_content' => '',
            ],
            'accept_language' => (string) $request->header('Accept-Language', ''),
            'event_properties' => [],
            'metadata' => [],
        ];
    }

    private function pathFromUrl(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        return parse_url($url, PHP_URL_PATH) ?: '/';
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPayload(Request $request): array
    {
        return $this->normalizePayload($request, $request->validate($this->payloadRules()));
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function payloadRules(string $prefix = ''): array
    {
        return [
            "{$prefix}visitor_id" => ['required', 'string', 'max:255'],
            "{$prefix}type" => ['required', 'string', 'max:255'],
            "{$prefix}url" => ['nullable', 'url', 'max:2048'],
            "{$prefix}path" => ['nullable', 'string', 'max:2048'],
            "{$prefix}referrer" => ['nullable', 'url', 'max:2048'],
            "{$prefix}utms.utm_source" => ['nullable', 'string', 'max:255'],
            "{$prefix}utms.utm_medium" => ['nullable', 'string', 'max:255'],
            "{$prefix}utms.utm_campaign" => ['nullable', 'string', 'max:255'],
            "{$prefix}utms.utm_term" => ['nullable', 'string', 'max:255'],
            "{$prefix}utms.utm_content" => ['nullable', 'string', 'max:255'],
            "{$prefix}accept_language" => ['nullable', 'string', 'max:1000'],
            "{$prefix}event_properties" => ['array'],
            "{$prefix}metadata" => ['array'],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function normalizePayload(Request $request, array $payload): array
    {
        $acceptLanguage = trim((string) ($payload['accept_language'] ?? ''));

        if ($acceptLanguage === '') {
            $acceptLanguage = (string) $request->header('Accept-Language', '');
        }

        return [
            'visitor_id' => $payload['visitor_id'],
            'type' => $payload['type'],
            'url' => $payload['url'] ?? null,
            'referrer' => $payload['referrer'] ?? null,
            'utms' => [
                'utm_source' => data_get($payload, 'utms.utm_source'),
                'utm_medium' => data_get($payload, 'utms.utm_medium'),
                'utm_campaign' => data_get($payload, 'utms.utm_campaign'),
                'utm_term' => data_get($payload, 'utms.utm_term'),
                'utm_content' => data_get($payload, 'utms.utm_content'),
            ],
            'accept_language' => $acceptLanguage,
            'event_properties' => $payload['event_properties'] ?? [],
            'metadata' => $payload['metadata'] ?? [],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, array<string, mixed>>
     */
    private function diagnosePayload(Request $request, Project $project, SegmentEngine $engine, array $payload): array
    {
        if (($payload['accept_language'] ?? '') !== '') {
            $request->headers->set('Accept-Language', $payload['accept_language']);
        }

        $log = new EventLog([
            'project_id' => $project->id,
            'session_id' => $request->hasSession() ? session()->getId() : null,
            'uuid' => uniqid('', true),
            'visitor_id' => $payload['visitor_id'],
            'event_type' => $payload['type'],
            'page_url' => $payload['url'] ?? null,
            'page_path' => $this->pathFromUrl($payload['url'] ?? null),
            'referrer_url' => $payload['referrer'] ?? null,
            'utm_source' => data_get($payload, 'utms.utm_source'),
            'utm_medium' => data_get($payload, 'utms.utm_medium'),
            'utm_campaign' => data_get($payload, 'utms.utm_campaign'),
            'utm_term' => data_get($payload, 'utms.utm_term'),
            'utm_content' => data_get($payload, 'utms.utm_content'),
            'event_properties' => $payload['event_properties'] ?? [],
            'metadata' => $payload['metadata'] ?? [],
        ]);

        return $engine->diagnoseSegments($log)->values()->all();
    }

    private function ensureScenarioBelongsToProject(Project $project, DiagnosticScenario $diagnosticScenario): void
    {
        abort_unless($diagnosticScenario->project_id === $project->id, 404);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, array<string, mixed>>|null  $diagnostics
     */
    private function render(Project $project, array $payload, ?array $diagnostics, ?string $evaluatedAt): Response
    {
        return Inertia::render('Projects/Diagnostics', [
            'project' => $project,
            'organization' => $this->organizationContext($project->organization),
            'payload' => $payload,
            'diagnostics' => $diagnostics,
            'evaluatedAt' => $evaluatedAt,
            'savedScenarios' => $project->diagnosticScenarios()
                ->orderBy('name')
                ->get()
                ->map(fn (DiagnosticScenario $scenario) => [
                    'id' => $scenario->id,
                    'name' => $scenario->name,
                    'payload' => $scenario->payload,
                    'last_result' => $scenario->last_result,
                    'last_run_at' => $scenario->last_run_at?->toIso8601String(),
                ]),
        ]);
    }
}
