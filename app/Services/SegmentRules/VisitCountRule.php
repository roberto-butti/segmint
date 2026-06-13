<?php

namespace App\Services\SegmentRules;

use Illuminate\Support\Facades\DB;

class VisitCountRule extends AbstractRule
{
    public function passes(array $logValues, bool $includeCurrentEvent = false): bool
    {
        $visitorId = $logValues['visitor_id'];
        $minVisits = (int) $this->rule->value;
        $eventType = $this->rule->key ?: 'page-view';

        $total = DB::table('event_logs')
            ->where('visitor_id', $visitorId)
            ->where('event_type', $eventType)
            ->count();

        if ($includeCurrentEvent && ($logValues['event_type'] ?? null) === $eventType) {
            $total++;
        }

        return $total >= $minVisits;
    }
}
