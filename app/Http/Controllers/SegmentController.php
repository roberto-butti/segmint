<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSegmentRequest;
use App\Http\Requests\UpdateSegmentRequest;
use App\Models\Project;
use App\Models\Segment;
use App\Services\SegmentRules\SegmentRuleOperator;
use App\Services\SegmentRules\SegmentRuleType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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
            ->withCount('rules')
            ->latest()
            ->get();

        return Inertia::render('Segments/Index', [
            'project' => $project,
            'segments' => $segments,
        ]);
    }

    /**
     * Show the form for creating a new segment.
     */
    public function create(Request $request, Project $project): Response
    {
        abort_unless($project->user_id === $request->user()->id, 403);

        return Inertia::render('Segments/Create', [
            'project' => $project,
            'ruleTypes' => $this->enumOptions(SegmentRuleType::class),
            'ruleOperators' => $this->enumOptions(SegmentRuleOperator::class),
        ]);
    }

    /**
     * Store a newly created segment.
     */
    public function store(StoreSegmentRequest $request, Project $project): RedirectResponse
    {
        $segment = $project->segments()->create([
            'name' => $request->validated('name'),
            'slug' => Str::slug($request->validated('name')),
            'description' => $request->validated('description'),
            'active' => $request->validated('active'),
        ]);

        $this->syncRules($segment, $request->validated('rules', []));

        return redirect()->route('projects.segments.index', $project->slug);
    }

    /**
     * Display the specified segment.
     */
    public function show(Request $request, Project $project, Segment $segment): Response
    {
        abort_unless($project->user_id === $request->user()->id, 403);
        abort_unless($segment->project_id === $project->id, 404);

        $segment->load('rules');

        return Inertia::render('Segments/Show', [
            'project' => $project,
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
        abort_unless($project->user_id === $request->user()->id, 403);
        abort_unless($segment->project_id === $project->id, 404);

        $segment->load('rules');

        return Inertia::render('Segments/Edit', [
            'project' => $project,
            'segment' => $segment,
            'ruleTypes' => $this->enumOptions(SegmentRuleType::class),
            'ruleOperators' => $this->enumOptions(SegmentRuleOperator::class),
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
            'slug' => Str::slug($request->validated('name')),
            'description' => $request->validated('description'),
            'active' => $request->validated('active'),
        ]);

        $this->syncRules($segment, $request->validated('rules', []));

        return redirect()->route('projects.segments.index', $project->slug);
    }

    /**
     * Duplicate an existing segment with a new name.
     */
    public function duplicate(Request $request, Project $project, Segment $segment): RedirectResponse
    {
        abort_unless($project->user_id === $request->user()->id, 403);
        abort_unless($segment->project_id === $project->id, 404);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:segments,slug'],
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

        return redirect()->route('projects.segments.edit', [$project->slug, $newSegment]);
    }

    /**
     * Delete the specified segment and its rules.
     */
    public function destroy(Request $request, Project $project, Segment $segment): RedirectResponse
    {
        abort_unless($project->user_id === $request->user()->id, 403);
        abort_unless($segment->project_id === $project->id, 404);

        $segment->rules()->delete();
        $segment->delete();

        return redirect()->route('projects.segments.index', $project->slug);
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
                'key' => $rule['key'],
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
