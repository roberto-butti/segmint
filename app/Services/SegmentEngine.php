<?php

namespace App\Services;

use App\Models\EventLog;
use App\Models\Segment;
use App\Models\SegmentMatch;
use App\Models\Visitor;
use App\Services\SegmentRules\RuleFactory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class SegmentEngine
{
    /**
     * Assign segments to a visitor based on stored attributes.
     *
     * @return Collection<int, Segment>
     */
    public function assignSegments(EventLog $log): Collection
    {
        $segments = Segment::with('rules')
            ->where('project_id', $log->project_id)
            ->where('active', true)
            ->get();

        $assigned = collect();
        $now = now();
        $matchRecords = [];

        foreach ($segments as $segment) {
            $matched = $this->matchesSegment($log, $segment);

            $matchRecords[] = [
                'event_log_id' => $log->id,
                'segment_id' => $segment->id,
                'matched' => $matched,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if ($matched) {
                Log::info("$segment assigned");
                $assigned->push($segment->id);
            }
        }

        if (! empty($matchRecords)) {
            SegmentMatch::insert($matchRecords);
        }

        return Segment::whereIn('id', $assigned)->get();
    }

    /**
     * Determine if the visitor matches the segment rules.
     */
    protected function matchesSegment(EventLog $log, Segment $segment): bool
    {
        $logValues = $log->attributesToArray();

        foreach ($segment->rules as $rule) {
            $handler = RuleFactory::make($rule);

            if (! $handler->passes($logValues)) {
                return false;
            }
        }

        return true;
    }
}
