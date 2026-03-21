<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\RuleTemplate;
use App\Services\SegmentRules\SegmentRuleOperator;
use App\Services\SegmentRules\SegmentRuleType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        return Inertia::render('RuleTemplates/Index', [
            'project' => $project,
            'templates' => $templates,
            'ruleTypes' => $this->enumOptions(SegmentRuleType::class),
            'ruleOperators' => $this->enumOptions(SegmentRuleOperator::class),
        ]);
    }

    /**
     * Store a newly created rule template.
     */
    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('view', $project);

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

        return redirect()->route('projects.rule-templates.index', $project->slug);
    }

    /**
     * Update the specified rule template.
     */
    public function update(Request $request, Project $project, RuleTemplate $ruleTemplate): RedirectResponse
    {
        $this->authorize('view', $project);
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

        return redirect()->route('projects.rule-templates.index', $project->slug);
    }

    /**
     * Remove the specified rule template.
     */
    public function destroy(Request $request, Project $project, RuleTemplate $ruleTemplate): RedirectResponse
    {
        $this->authorize('view', $project);
        abort_unless($ruleTemplate->project_id === $project->id, 404);

        $ruleTemplate->delete();

        return redirect()->route('projects.rule-templates.index', $project->slug);
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
