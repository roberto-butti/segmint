<?php

namespace App\Http\Controllers;

use App\Http\Requests\CopySegmentsRequest;
use App\Http\Requests\StoreSegmentRequest;
use App\Http\Requests\UpdateSegmentRequest;
use App\Models\Project;
use App\Models\Segment;
use App\Services\SegmentRules\SegmentRuleOperator;
use App\Services\SegmentRules\SegmentRuleType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SegmentController extends Controller
{
    /**
     * Display a listing of segments for the given project.
     */
    public function index(Request $request, Project $project): Response
    {
        $this->authorize('view', $project);

        $segments = $project->segments()
            ->withCount('rules')
            ->latest()
            ->get();

        $destinationProjects = $request->user()
            ->accessibleProjects()
            ->whereKeyNot($project->id)
            ->with([
                'organization:id,name',
                'segments' => fn ($query) => $query
                    ->select(['id', 'project_id', 'slug'])
                    ->whereIn('slug', $segments->pluck('slug')),
            ])
            ->orderBy('name')
            ->get()
            ->filter(fn (Project $destination) => $request->user()->can('update', $destination))
            ->map(fn (Project $destination) => [
                'id' => $destination->id,
                'name' => $destination->name,
                'public_id' => $destination->public_id,
                'organization_name' => $destination->organization->name,
                'segment_slugs' => $destination->segments->pluck('slug')->values(),
            ])
            ->values();

        return Inertia::render('Segments/Index', [
            'project' => $project,
            'organization' => $this->organizationContext($project->organization),
            'segments' => $segments,
            'destinationProjects' => $destinationProjects,
            'canManageProject' => $request->user()->can('update', $project),
        ]);
    }

    /**
     * Show the form for creating a new segment.
     */
    public function create(Request $request, Project $project): Response
    {
        $this->authorize('update', $project);

        return Inertia::render('Segments/Create', [
            'project' => $project,
            'organization' => $this->organizationContext($project->organization),
            'ruleTypes' => $this->enumOptions(SegmentRuleType::class),
            'ruleOperators' => $this->enumOptions(SegmentRuleOperator::class),
            'ruleTemplates' => $project->ruleTemplates()->orderBy('name')->get(),
        ]);
    }

    /**
     * Store a newly created segment.
     */
    public function store(StoreSegmentRequest $request, Project $project): RedirectResponse
    {
        $segment = $project->segments()->create([
            'name' => $request->validated('name'),
            'slug' => $request->validated('slug'),
            'description' => $request->validated('description'),
            'active' => $request->validated('active'),
        ]);

        $this->syncRules($segment, $request->validated('rules', []));

        return redirect()->route('projects.segments.edit', [$project, $segment]);
    }

    /**
     * Display the specified segment.
     */
    public function show(Request $request, Project $project, Segment $segment): Response
    {
        $this->authorize('update', $project);
        abort_unless($segment->project_id === $project->id, 404);

        $segment->load('rules');

        return Inertia::render('Segments/Show', [
            'project' => $project,
            'organization' => $this->organizationContext($project->organization),
            'segment' => $segment,
            'ruleTypes' => $this->enumOptions(SegmentRuleType::class),
            'ruleOperators' => $this->enumOptions(SegmentRuleOperator::class),
        ]);
    }

    /**
     * Show the form for editing the specified segment.
     */
    public function edit(Request $request, Project $project, Segment $segment): Response
    {
        $this->authorize('update', $project);
        abort_unless($segment->project_id === $project->id, 404);

        $segment->load('rules');

        return Inertia::render('Segments/Edit', [
            'project' => $project,
            'organization' => $this->organizationContext($project->organization),
            'segment' => $segment,
            'ruleTypes' => $this->enumOptions(SegmentRuleType::class),
            'ruleOperators' => $this->enumOptions(SegmentRuleOperator::class),
            'ruleTemplates' => $project->ruleTemplates()->orderBy('name')->get(),
        ]);
    }

    /**
     * Update the specified segment.
     */
    public function update(UpdateSegmentRequest $request, Project $project, Segment $segment): RedirectResponse
    {
        abort_unless($segment->project_id === $project->id, 404);

        $segment->update([
            'name' => $request->validated('name'),
            'slug' => $request->validated('slug'),
            'description' => $request->validated('description'),
            'active' => $request->validated('active'),
        ]);

        $this->syncRules($segment, $request->validated('rules', []));

        return redirect()->route('projects.segments.index', $project);
    }

    /**
     * Duplicate an existing segment with a new name.
     */
    public function duplicate(Request $request, Project $project, Segment $segment): RedirectResponse
    {
        $this->authorize('update', $project);
        abort_unless($segment->project_id === $project->id, 404);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('segments')->where('project_id', $project->id),
            ],
        ]);

        $segment->load('rules');

        $newSegment = $project->segments()->create([
            'name' => $request->input('name'),
            'slug' => Str::slug($request->input('slug')),
            'description' => $segment->description,
            'active' => $segment->active,
        ]);

        foreach ($segment->rules as $rule) {
            $newSegment->rules()->create([
                'type' => $rule->type,
                'key' => $rule->key,
                'operator' => $rule->operator,
                'value' => $rule->value,
                'priority' => $rule->priority,
            ]);
        }

        return redirect()->route('projects.segments.edit', [$project, $newSegment]);
    }

    /**
     * Copy selected segments and their rules into another project.
     */
    public function copy(CopySegmentsRequest $request, Project $project): RedirectResponse
    {
        $destination = Project::findOrFail($request->validated('destination_project_id'));
        $this->authorize('update', $destination);

        $segments = $project->segments()
            ->with('rules')
            ->whereKey($request->validated('segment_ids'))
            ->get();

        $existingSlugs = $destination->segments()
            ->whereIn('slug', $segments->pluck('slug'))
            ->pluck('slug');

        $segmentsToCopy = $segments->whereNotIn('slug', $existingSlugs);

        DB::transaction(function () use ($destination, $segmentsToCopy): void {
            foreach ($segmentsToCopy as $segment) {
                $newSegment = $destination->segments()->create([
                    'name' => $segment->name,
                    'slug' => $segment->slug,
                    'description' => $segment->description,
                    'active' => $segment->active,
                ]);

                $newSegment->rules()->createMany($segment->rules->map(fn ($rule) => [
                    'type' => $rule->type,
                    'key' => $rule->key,
                    'operator' => $rule->operator,
                    'value' => $rule->value,
                    'priority' => $rule->priority,
                ])->all());
            }
        });

        $copiedCount = $segmentsToCopy->count();
        $skippedCount = $segments->count() - $copiedCount;
        $message = "{$copiedCount} ".Str::plural('segment', $copiedCount)." copied into {$destination->name}.";

        if ($skippedCount > 0) {
            $message .= " {$skippedCount} ".Str::plural('segment', $skippedCount).' skipped because the slug already exists.';
        }

        return redirect()
            ->route('projects.segments.index', $project)
            ->with('segmentCopy', [
                'id' => Str::uuid()->toString(),
                'message' => $message,
                'destination_name' => $destination->name,
                'destination_url' => route('projects.segments.index', $destination),
            ]);
    }

    /**
     * Delete the specified segment and its rules.
     */
    public function destroy(Request $request, Project $project, Segment $segment): RedirectResponse
    {
        $this->authorize('view', $project);
        abort_unless($segment->project_id === $project->id, 404);

        $segment->rules()->delete();
        $segment->delete();

        return redirect()->route('projects.segments.index', $project);
    }

    /**
     * Sync segment rules by replacing all existing rules.
     *
     * @param  array<int, array<string, mixed>>  $rules
     */
    private function syncRules(Segment $segment, array $rules): void
    {
        $segment->rules()->delete();

        foreach ($rules as $index => $rule) {
            $segment->rules()->create([
                'type' => $rule['type'],
                'key' => $rule['key'] ?? '',
                'operator' => $rule['operator'],
                'value' => $rule['value'],
                'priority' => $rule['priority'] ?? $index,
            ]);
        }
    }

    /**
     * Convert a backed enum to an array of {value, label} options.
     *
     * @return array<int, array{value: string, label: string}>
     */
    private function enumOptions(string $enumClass): array
    {
        return array_map(
            fn ($case) => ['value' => $case->value, 'label' => $case->label()],
            $enumClass::cases()
        );
    }
}
