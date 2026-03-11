<?php

namespace App\Services\SegmentRules;

enum SegmentRuleType: string
{
    case COMPARISON = 'comparison';
    case VISIT_COUNT = 'visit_count';
    case BROWSER_LANGUAGE = 'browser_language';
}
