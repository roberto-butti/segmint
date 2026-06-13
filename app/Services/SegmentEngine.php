<?php

namespace App\Services;

use App\Models\EventLog;
use App\Models\Segment;
use App\Models\SegmentMatch;
use App\Services\SegmentRules\RuleFactory;
use Illuminate\Support\Collection;

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
}
