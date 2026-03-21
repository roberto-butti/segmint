<?php

namespace App\Services\SegmentRules;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class PageViewCountRule extends AbstractRule
{
    public function passes(array $logValues): bool
    {
        $visitorId = $logValues['visitor_id'];
        $minViews = (int) $this->rule->value;
        $currentPath = Arr::get($logValues, 'navigation_info.path');

        if ($currentPath === null) {
            return false;
        }

        $total = DB::table('event_logs')
            ->where('visitor_id', $visitorId)
            ->where('event_type', 'page-view')
            ->where('navigation_info->path', $currentPath)
            ->count();

        return $total >= $minViews;
    }
}
