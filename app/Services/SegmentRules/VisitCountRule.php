<?php

namespace App\Services\SegmentRules;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VisitCountRule extends AbstractRule
{
    public function passes(array $logValues): bool
    {
        $visitorId = $logValues['visitor_id'];
        $minVisits = (int) $this->rule->value;

        // $count = DB::table("event_logs")->where("visitor_id", $userId)->count();
        $total = DB::table('event_logs')
            ->where('visitor_id', $visitorId)
            ->where('event_type', 'page-view')
            ->whereRaw("event_properties->>'path' = ?", [
                (string) Arr::get($logValues, 'event_properties.path'),
            ])
            ->count();
        // dd($total);
        Log::info(
            $total.
                ' '.
                $minVisits.
                ' '.
                Arr::get($logValues, 'event_properties.path').
                ' '.
                'personalization/personalized-landing-page',
        );

        return $total >= $minVisits;
    }
}
