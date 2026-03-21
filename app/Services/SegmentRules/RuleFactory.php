<?php

namespace App\Services\SegmentRules;

use App\Models\SegmentRule;

class RuleFactory
{
    public static function make(SegmentRule $rule): RuleInterface
    {
        return match ($rule->type) {
            SegmentRuleType::Comparison => new ComparisonRule($rule),
            SegmentRuleType::VisitCount => new VisitCountRule($rule),
            SegmentRuleType::PageViewCount => new PageViewCountRule($rule),
            SegmentRuleType::BrowserLanguage => new BrowserLanguageRule($rule),
            default => new ComparisonRule($rule),
        };
    }
}
