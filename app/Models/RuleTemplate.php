<?php

namespace App\Models;

use App\Services\SegmentRules\SegmentRuleOperator;
use App\Services\SegmentRules\SegmentRuleType;
use Database\Factories\RuleTemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['project_id', 'name', 'type', 'key', 'operator', 'value'])]
class RuleTemplate extends Model
{
    /** @use HasFactory<RuleTemplateFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => SegmentRuleType::class,
            'operator' => SegmentRuleOperator::class,
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Default templates to seed when a project is created.
     *
     * @return array<int, array{name: string, type: string, key: string, operator: string, value: string}>
     */
    public static function defaults(): array
    {
        return [
            [
                'name' => 'UTM Campaign match',
                'type' => 'comparison',
                'key' => 'utms.utm_campaign',
                'operator' => '=',
                'value' => '',
            ],
            [
                'name' => 'UTM Source match',
                'type' => 'comparison',
                'key' => 'utms.utm_source',
                'operator' => '=',
                'value' => '',
            ],
            [
                'name' => 'Google visitors',
                'type' => 'comparison',
                'key' => 'utms.utm_source',
                'operator' => '=',
                'value' => 'google',
            ],
            [
                'name' => 'Facebook visitors',
                'type' => 'comparison',
                'key' => 'utms.utm_source',
                'operator' => '=',
                'value' => 'facebook',
            ],
            [
                'name' => 'UTM Medium match',
                'type' => 'comparison',
                'key' => 'utms.utm_medium',
                'operator' => '=',
                'value' => '',
            ],
            [
                'name' => 'Frequent page visitor (5+)',
                'type' => 'page_view_count',
                'key' => '',
                'operator' => '>=',
                'value' => '5',
            ],
            [
                'name' => 'Returning visitor (3+ pages)',
                'type' => 'visit_count',
                'key' => 'page-view',
                'operator' => '>=',
                'value' => '3',
            ],
            [
                'name' => 'High engagement (10+ pages)',
                'type' => 'visit_count',
                'key' => 'page-view',
                'operator' => '>=',
                'value' => '10',
            ],
            [
                'name' => 'Italian visitors',
                'type' => 'browser_language',
                'key' => 'Accept-Language',
                'operator' => '=',
                'value' => 'it',
            ],
            [
                'name' => 'English visitors',
                'type' => 'browser_language',
                'key' => 'Accept-Language',
                'operator' => '=',
                'value' => 'en',
            ],
            [
                'name' => 'Page path contains',
                'type' => 'comparison',
                'key' => 'navigation_info.path',
                'operator' => 'contains',
                'value' => '',
            ],
            [
                'name' => 'Referrer contains',
                'type' => 'comparison',
                'key' => 'navigation_info.referrer_url',
                'operator' => 'contains',
                'value' => '',
            ],
        ];
    }
}
