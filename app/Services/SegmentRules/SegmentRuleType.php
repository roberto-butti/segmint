<?php

namespace App\Services\SegmentRules;

enum SegmentRuleType: string
{
    case Comparison = 'comparison';
    case VisitCount = 'visit_count';
    case PageViewCount = 'page_view_count';
    case BrowserLanguage = 'browser_language';

    public function label(): string
    {
        return match ($this) {
            self::Comparison => 'Comparison',
            self::VisitCount => 'Visit count (all pages)',
            self::PageViewCount => 'Page view count (same page)',
            self::BrowserLanguage => 'Browser language',
        };
    }
}
