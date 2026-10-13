<?php

namespace Tests\Unit;

use App\Models\SegmentRule;
use App\Services\SegmentRules\ComparisonRule;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ComparisonRuleTest extends TestCase
{
    #[DataProvider('comparisonCases')]
    public function test_it_evaluates_supported_operators(
        mixed $actual,
        string $operator,
        string $expected,
        bool $passes,
    ): void {
        $rule = new SegmentRule([
            'type' => 'comparison',
            'key' => 'value',
            'operator' => $operator,
            'value' => $expected,
        ]);

        $this->assertSame($passes, (new ComparisonRule($rule))->passes([
            'value' => $actual,
        ]));
    }

    /**
     * @return array<string, array{mixed, string, string, bool}>
     */
    public static function comparisonCases(): array
    {
        return [
            'equals' => ['google', '=', 'google', true],
            'not equals' => ['google', '!=', 'facebook', true],
            'greater than' => [10, '>', '5', true],
            'greater than or equal' => [5, '>=', '5', true],
            'less than' => [4, '<', '5', true],
            'less than or equal' => [5, '<=', '5', true],
            'contains' => ['https://google.com/search', 'contains', 'google.com', true],
            'does not contain' => ['https://example.com', 'not_contains', 'google.com', true],
            'starts with' => ['/products/shoes', 'starts_with', '/products', true],
            'ends with' => ['report.pdf', 'ends_with', '.pdf', true],
            'in comma-separated list' => ['organic', 'in', 'paid, organic, referral', true],
            'delimited regex' => ['https://google.com', 'regex', '/^https:\/\/google\./', true],
            'plain regex' => ['/products/42', 'regex', '^/products/[0-9]+$', true],
            'invalid regex' => ['anything', 'regex', '[', false],
            'missing value does not satisfy negative string comparison' => [null, 'not_contains', 'google', false],
        ];
    }
}
