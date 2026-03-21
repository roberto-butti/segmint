<?php

namespace App\Services\SegmentRules;

enum SegmentRuleOperator: string
{
    case Equals = '=';
    case NotEquals = '!=';
    case GreaterThan = '>';
    case GreaterThanOrEqual = '>=';
    case LessThan = '<';
    case LessThanOrEqual = '<=';
    case Contains = 'contains';
    case NotContains = 'not_contains';
    case StartsWith = 'starts_with';
    case EndsWith = 'ends_with';
    case In = 'in';
    case Regex = 'regex';

    public function label(): string
    {
        return match ($this) {
            self::Equals => 'Equals',
            self::NotEquals => 'Not equals',
            self::GreaterThan => 'Greater than',
            self::GreaterThanOrEqual => 'Greater than or equal',
            self::LessThan => 'Less than',
            self::LessThanOrEqual => 'Less than or equal',
            self::Contains => 'Contains',
            self::NotContains => 'Does not contain',
            self::StartsWith => 'Starts with',
            self::EndsWith => 'Ends with',
            self::In => 'In list',
            self::Regex => 'Matches regex',
        };
    }
}
