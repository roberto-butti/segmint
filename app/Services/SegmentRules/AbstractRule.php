<?php

namespace App\Services\SegmentRules;

use App\Models\SegmentRule;

abstract class AbstractRule implements RuleInterface
{
    public function __construct(protected SegmentRule $rule) {}
}
