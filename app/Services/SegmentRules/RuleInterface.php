<?php

namespace App\Services\SegmentRules;

interface RuleInterface
{
    public function passes(array $logValues): bool;
}
