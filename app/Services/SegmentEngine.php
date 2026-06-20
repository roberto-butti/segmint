<?php

namespace App\Services;

use App\Models\EventLog;
use App\Models\Segment;
use App\Models\SegmentMatch;
use App\Services\SegmentRules\RuleFactory;
use App\Services\SegmentRules\SegmentRuleOperator;
use App\Services\SegmentRules\SegmentRuleType;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SegmentEngine
{
    /**
     * Assign segments to a visitor based on stored attributes.
     *
     * @return Collection<int, Segment>
     */
    public function assignSegments(EventLog $log): Collection
    {
        return $this->matchSegments($log, persistMatches: true);
    }

    /**
     * Evaluate segments for an unsaved event without storing match records.
     *
     * @return Collection<int, Segment>
     */
    public function evaluateSegments(EventLog $log): Collection
    {
        return $this->matchSegments($log, persistMatches: false);
    }

    /**
     * Explain how each active segment evaluates for an unsaved candidate event.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function diagnoseSegments(EventLog $log): Collection
    {
        return Segment::with('rules')
            ->where('project_id', $log->project_id)
            ->where('active', true)
            ->orderBy('name')
            ->get()
            ->map(function (Segment $segment) use ($log) {
                $logValues = $log->attributesToArray();
                $includeCurrentEvent = ! $log->exists;
                $rules = $segment->rules
                    ->map(fn ($rule) => $this->diagnoseRule($logValues, $rule, $includeCurrentEvent))
                    ->values();

                return [
                    'id' => $segment->id,
                    'name' => $segment->name,
                    'slug' => $segment->slug,
                    'description' => $segment->description,
                    'matched' => $rules->every(fn (array $rule) => $rule['passed']),
                    'rules' => $rules,
                ];
            });
    }

    /**
     * @return Collection<int, Segment>
     */
    private function matchSegments(EventLog $log, bool $persistMatches): Collection
    {
        $segments = Segment::with('rules')
            ->where('project_id', $log->project_id)
            ->where('active', true)
            ->get();

        $assigned = collect();
        $now = now();
        $matchRecords = [];

        foreach ($segments as $segment) {
            $matched = $this->matchesSegment($log, $segment, includeCurrentEvent: ! $log->exists);

            if ($persistMatches) {
                $matchRecords[] = [
                    'event_log_id' => $log->id,
                    'segment_id' => $segment->id,
                    'matched' => $matched,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if ($matched) {
                $assigned->push($segment->id);
            }
        }

        if ($persistMatches && ! empty($matchRecords)) {
            SegmentMatch::insert($matchRecords);
        }

        return Segment::whereIn('id', $assigned)->get();
    }

    /**
     * Determine if the visitor matches the segment rules.
     */
    protected function matchesSegment(EventLog $log, Segment $segment, bool $includeCurrentEvent): bool
    {
        $logValues = $log->attributesToArray();

        foreach ($segment->rules as $rule) {
            $handler = RuleFactory::make($rule);

            if (! $handler->passes($logValues, $includeCurrentEvent)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    private function diagnoseRule(array $logValues, $rule, bool $includeCurrentEvent): array
    {
        $handler = RuleFactory::make($rule);
        $passed = $handler->passes($logValues, $includeCurrentEvent);
        $type = $rule->type instanceof SegmentRuleType ? $rule->type->value : $rule->type;
        $operator = $rule->operator instanceof SegmentRuleOperator ? $rule->operator->value : $rule->operator;

        return [
            'id' => $rule->id,
            'type' => $type,
            'type_label' => $rule->type instanceof SegmentRuleType ? $rule->type->label() : $type,
            'key' => $rule->key,
            'operator' => $operator,
            'operator_label' => $rule->operator instanceof SegmentRuleOperator ? $rule->operator->label() : $operator,
            'expected' => $this->expectedValue($rule, $type, $operator),
            'actual' => $this->actualValue($logValues, $rule, $type, $includeCurrentEvent),
            'passed' => $passed,
            'priority' => $rule->priority,
            'note' => $this->diagnosticNote($logValues, $rule, $type, $includeCurrentEvent),
        ];
    }

    private function expectedValue($rule, string $type, string $operator): string
    {
        return match ($type) {
            SegmentRuleType::VisitCount->value => 'at least '.$rule->value.' '.$this->eventTypeForVisitCount($rule).' events',
            SegmentRuleType::PageViewCount->value => 'at least '.$rule->value.' page views for the candidate path',
            SegmentRuleType::BrowserLanguage->value => "{$operator} {$rule->value}",
            default => "{$rule->key} {$operator} {$rule->value}",
        };
    }

    private function actualValue(array $logValues, $rule, string $type, bool $includeCurrentEvent): mixed
    {
        return match ($type) {
            SegmentRuleType::VisitCount->value => $this->visitCount($logValues, $rule, $includeCurrentEvent),
            SegmentRuleType::PageViewCount->value => $this->pageViewCount($logValues, $includeCurrentEvent),
            SegmentRuleType::BrowserLanguage->value => (string) request()->header($rule->key ?: 'Accept-Language'),
            default => Arr::get($logValues, $rule->key),
        };
    }

    private function diagnosticNote(array $logValues, $rule, string $type, bool $includeCurrentEvent): ?string
    {
        return match ($type) {
            SegmentRuleType::VisitCount->value => $includeCurrentEvent
                ? 'Count includes the candidate event when its type matches the rule event type.'
                : null,
            SegmentRuleType::PageViewCount->value => ($logValues['page_path'] ?? null) === null
                ? 'The candidate event has no page path, so this rule cannot pass.'
                : 'Count includes the candidate event when it is a page-view for this path.',
            SegmentRuleType::BrowserLanguage->value => request()->header($rule->key ?: 'Accept-Language') === null
                ? 'No matching request header was provided.'
                : null,
            default => null,
        };
    }

    private function visitCount(array $logValues, $rule, bool $includeCurrentEvent): int
    {
        $eventType = $this->eventTypeForVisitCount($rule);
        $total = DB::table('event_logs')
            ->where('visitor_id', $logValues['visitor_id'])
            ->where('event_type', $eventType)
            ->count();

        if ($includeCurrentEvent && ($logValues['event_type'] ?? null) === $eventType) {
            $total++;
        }

        return $total;
    }

    private function pageViewCount(array $logValues, bool $includeCurrentEvent): int
    {
        $currentPath = $logValues['page_path'] ?? null;

        if ($currentPath === null) {
            return 0;
        }

        $total = DB::table('event_logs')
            ->where('visitor_id', $logValues['visitor_id'])
            ->where('event_type', 'page-view')
            ->where('page_path', $currentPath)
            ->count();

        if ($includeCurrentEvent && ($logValues['event_type'] ?? null) === 'page-view') {
            $total++;
        }

        return $total;
    }

    private function eventTypeForVisitCount($rule): string
    {
        return $rule->key ?: 'page-view';
    }
}
