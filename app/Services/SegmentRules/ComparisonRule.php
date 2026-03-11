<?php

namespace App\Services\SegmentRules;

use Illuminate\Support\Arr;

class ComparisonRule extends AbstractRule
{
    public function passes(array $logValues): bool
    {
        $left = Arr::get($logValues, $this->rule->key);
        // dd($logValues, $this->rule->key, $left);
        $operator = $this->rule->operator;
        $right = $this->rule->value;

        return match ($operator) {
            '=' => $left == $right,
            '>' => $left > $right,
            '<' => $left < $right,
            '!=' => $left != $right,
            default => false,
        };
    }
}
