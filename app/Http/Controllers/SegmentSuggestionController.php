<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\SegmentRules\SegmentRuleOperator;
use App\Services\SegmentRules\SegmentRuleType;
use App\Services\SegmentSuggestionEngine;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SegmentSuggestionController extends Controller
{
    /**
     * Display segment suggestions based on event log analysis.
     */
    public function index(Request $request, Project $project, SegmentSuggestionEngine $engine): Response
    {
        $this->authorize('view', $project);

        $suggestions = $engine->suggest($project);

        return Inertia::render('Segments/Suggestions', [
            'project' => $project,
            'suggestions' => $suggestions,
            'ruleTypes' => array_map(
                fn ($case) => ['value' => $case->value, 'label' => $case->label()],
                SegmentRuleType::cases()
            ),
            'ruleOperators' => array_map(
                fn ($case) => ['value' => $case->value, 'label' => $case->label()],
                SegmentRuleOperator::cases()
            ),
        ]);
    }
}
