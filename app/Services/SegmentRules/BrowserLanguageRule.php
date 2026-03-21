<?php

namespace App\Services\SegmentRules;

class BrowserLanguageRule extends AbstractRule
{
    public function passes(array $logValues): bool
    {
        $preferred = strtolower($this->rule->value);
        $header = $this->rule->key ?: 'Accept-Language';
        $rawHeader = (string) request()->header($header);

        if ($rawHeader === '') {
            return false;
        }

        $operator = $this->rule->operator instanceof SegmentRuleOperator
            ? $this->rule->operator->value
            : $this->rule->operator;

        // Parse language tags: "en-US,en;q=0.9,it-IT;q=0.8,it;q=0.7" → ["en-us", "en", "it-it", "it"]
        $languages = array_map(function (string $part): string {
            return strtolower(trim(explode(';', $part)[0]));
        }, explode(',', $rawHeader));

        return match ($operator) {
            '=' => $this->matchesEquals($languages, $preferred),
            '!=' => ! $this->matchesEquals($languages, $preferred),
            'contains' => str_contains(strtolower($rawHeader), $preferred),
            'starts_with' => str_starts_with(strtolower($rawHeader), $preferred),
            default => $this->matchesEquals($languages, $preferred),
        };
    }

    /**
     * Check if the preferred language matches any tag in the list.
     * "it" matches both "it" and "it-IT".
     *
     * @param  array<int, string>  $languages
     */
    private function matchesEquals(array $languages, string $preferred): bool
    {
        foreach ($languages as $lang) {
            if ($lang === $preferred || str_starts_with($lang, $preferred.'-')) {
                return true;
            }
        }

        return false;
    }
}
