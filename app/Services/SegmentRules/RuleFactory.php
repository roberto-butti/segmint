<?php

namespace App\Services\SegmentRules;

class RuleFactory
{
    public static function make($rule): RuleInterface
    {
        return match ($rule->type) {
            SegmentRuleType::COMPARISON => new ComparisonRule($rule),
            SegmentRuleType::VISIT_COUNT => new VisitCountRule($rule),
            SegmentRuleType::BROWSER_LANGUAGE => new BrowserLanguageRule($rule),
            default => new ComparisonRule(
                $rule,
            ), /*
            default => throw new \Exception(
                "Unknown rule type: {$rule->type}",
            ),
            */
        };
    }
}
