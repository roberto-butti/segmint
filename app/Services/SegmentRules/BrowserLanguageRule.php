<?php

namespace App\Services\SegmentRules;

class BrowserLanguageRule extends AbstractRule
{
    public function passes(array $logValues): bool
    {
        $preferred = $this->rule->value; // ex: 'en'
        $userLang = request()->header('Accept-Language');

        return str_starts_with($userLang, $preferred);
    }
}
