<?php

namespace App\Services\SegmentRules;

use Illuminate\Support\Facades\DB;

class PageViewCountRule extends AbstractRule
{
    public function passes(array $logValues): bool
    {
        $visitorId = $logValues['visitor_id'];
        $minViews = (int) $this->rule->value;
        $currentPath = $logValues['page_path'] ?? null;

        if ($currentPath === null) {
            return false;
        }

        $total = DB::table('event_logs')
            ->where('visitor_id', $visitorId)
            ->where('event_type', 'page-view')
            ->where('page_path', $currentPath)
            ->count();

        return $total >= $minViews;
    }
}
