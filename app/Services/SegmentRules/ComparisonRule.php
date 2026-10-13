<?php

namespace App\Services\SegmentRules;

use Illuminate\Support\Arr;

class ComparisonRule extends AbstractRule
{
    public function passes(array $logValues, bool $includeCurrentEvent = false): bool
    {
        $left = Arr::get($logValues, $this->rule->key);
        $operator = $this->rule->operator;
        $right = $this->rule->value;
        $leftString = $this->comparableString($left);

        return match ($operator instanceof SegmentRuleOperator ? $operator->value : $operator) {
            '=' => $left == $right,
            '!=' => $left != $right,
            '>' => $left > $right,
            '>=' => $left >= $right,
            '<' => $left < $right,
            '<=' => $left <= $right,
            'contains' => $leftString !== null && str_contains($leftString, $right),
            'not_contains' => $leftString !== null && ! str_contains($leftString, $right),
            'starts_with' => $leftString !== null && str_starts_with($leftString, $right),
            'ends_with' => $leftString !== null && str_ends_with($leftString, $right),
            'in' => $leftString !== null && in_array($leftString, $this->listValues($right), true),
            'regex' => $leftString !== null && $this->matchesRegex($leftString, $right),
            default => false,
        };
    }

    private function comparableString(mixed $value): ?string
    {
        return is_scalar($value) ? (string) $value : null;
    }

    /**
     * @return array<int, string>
     */
    private function listValues(string $value): array
    {
        return array_map(trim(...), explode(',', $value));
    }

    private function matchesRegex(string $value, string $pattern): bool
    {
        if ($pattern === '') {
            return false;
        }

        $result = @preg_match($pattern, $value);

        if ($result === false) {
            $delimiter = '~';
            $escapedPattern = str_replace($delimiter, '\\'.$delimiter, $pattern);
            $result = @preg_match($delimiter.$escapedPattern.$delimiter.'u', $value);
        }

        return $result === 1;
    }
}
