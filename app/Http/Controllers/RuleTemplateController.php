<?php

namespace App\Http\Controllers;

use App\Http\Requests\CopyRuleTemplatesRequest;
use App\Models\Project;
use App\Models\RuleTemplate;
use App\Services\SegmentRules\SegmentRuleOperator;
use App\Services\SegmentRules\SegmentRuleType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class RuleTemplateController extends Controller
{
    /**
     * Display a listing of rule templates for the given project.
     */
    public function index(Request $request, Project $project): Response
    {
        $this->authorize('view', $project);

        $templates = $project->ruleTemplates()
            ->orderBy('name')
            ->get();

        $destinationProjects = $request->user()
            ->accessibleProjects()
            ->whereKeyNot($project->id)
            ->with([
                'organization:id,name',
                'ruleTemplates' => fn ($query) => $query
                    ->select(['id', 'project_id', 'name'])
                    ->whereIn('name', $templates->pluck('name')),
            ])
            ->orderBy('name')
            ->get()
            ->filter(fn (Project $destination) => $request->user()->can('update', $destination))
            ->map(fn (Project $destination) => [
                'id' => $destination->id,
                'name' => $destination->name,
                'public_id' => $destination->public_id,
                'organization_name' => $destination->organization->name,
                'rule_template_names' => $destination->ruleTemplates->pluck('name')->values(),
            ])
            ->values();

        return Inertia::render('RuleTemplates/Index', [
            'project' => $project,
            'organization' => $this->organizationContext($project->organization),
            'templates' => $templates,
            'destinationProjects' => $destinationProjects,
            'ruleTypes' => $this->enumOptions(SegmentRuleType::class),
            'ruleOperators' => $this->enumOptions(SegmentRuleOperator::class),
            'canManageProject' => $request->user()->can('update', $project),
        ]);
    }

    /**
     * Store a newly created rule template.
     */
    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(SegmentRuleType::class)],
            'key' => ['nullable', 'string', 'max:255'],
            'operator' => ['required', Rule::enum(SegmentRuleOperator::class)],
            'value' => ['nullable', 'string', 'max:1000'],
        ]);

        $project->ruleTemplates()->create([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'key' => $validated['key'] ?? '',
            'operator' => $validated['operator'],
            'value' => $validated['value'] ?? '',
        ]);

        return redirect()->route('projects.rule-templates.index', $project);
    }

    /**
     * Copy selected rule templates into another project.
     */
    public function copy(CopyRuleTemplatesRequest $request, Project $project): RedirectResponse
    {
        $destination = Project::findOrFail($request->validated('destination_project_id'));
        $this->authorize('update', $destination);

        $templates = $project->ruleTemplates()
            ->whereKey($request->validated('rule_template_ids'))
            ->get();

        $existingNames = $destination->ruleTemplates()
            ->whereIn('name', $templates->pluck('name'))
            ->pluck('name');

        $templatesToCopy = $templates->whereNotIn('name', $existingNames);

        DB::transaction(function () use ($destination, $templatesToCopy): void {
            $destination->ruleTemplates()->createMany($templatesToCopy->map(fn (RuleTemplate $template) => [
                'name' => $template->name,
                'type' => $template->type,
                'key' => $template->key,
                'operator' => $template->operator,
                'value' => $template->value,
            ])->all());
        });

        $copiedCount = $templatesToCopy->count();
        $skippedCount = $templates->count() - $copiedCount;
        $message = "{$copiedCount} rule ".Str::plural('template', $copiedCount)." copied into {$destination->name}.";

        if ($skippedCount > 0) {
            $message .= " {$skippedCount} rule ".Str::plural('template', $skippedCount).' skipped because the name already exists.';
        }

        return redirect()
            ->route('projects.rule-templates.index', $project)
            ->with('ruleTemplateCopy', [
                'id' => Str::uuid()->toString(),
                'message' => $message,
                'destination_name' => $destination->name,
                'destination_url' => route('projects.rule-templates.index', $destination),
            ]);
    }

    /**
     * Update the specified rule template.
     */
    public function update(Request $request, Project $project, RuleTemplate $ruleTemplate): RedirectResponse
    {
        $this->authorize('update', $project);
        abort_unless($ruleTemplate->project_id === $project->id, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(SegmentRuleType::class)],
            'key' => ['nullable', 'string', 'max:255'],
            'operator' => ['required', Rule::enum(SegmentRuleOperator::class)],
            'value' => ['nullable', 'string', 'max:1000'],
        ]);

        $ruleTemplate->update([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'key' => $validated['key'] ?? '',
            'operator' => $validated['operator'],
            'value' => $validated['value'] ?? '',
        ]);

        return redirect()->route('projects.rule-templates.index', $project);
    }

    /**
     * Remove the specified rule template.
     */
    public function destroy(Request $request, Project $project, RuleTemplate $ruleTemplate): RedirectResponse
    {
        $this->authorize('update', $project);
        abort_unless($ruleTemplate->project_id === $project->id, 404);

        $ruleTemplate->delete();

        return redirect()->route('projects.rule-templates.index', $project);
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
