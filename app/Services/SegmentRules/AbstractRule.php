<?php

namespace App\Services\SegmentRules;

abstract class AbstractRule implements RuleInterface
{
    protected $rule;

    public function __construct($rule)
    {
        $this->rule = $rule;
    }
}
