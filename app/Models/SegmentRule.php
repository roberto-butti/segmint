<?php

namespace App\Models;

use App\Services\SegmentRules\SegmentRuleOperator;
use App\Services\SegmentRules\SegmentRuleType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['segment_id', 'type', 'key', 'operator', 'value', 'priority'])]
class SegmentRule extends Model
{
    protected function casts(): array
    {
        return [
            'segment_id' => 'integer',
            'type' => SegmentRuleType::class,
            'operator' => SegmentRuleOperator::class,
            'priority' => 'integer',
        ];
    }

    public function segment(): BelongsTo
    {
        return $this->belongsTo(Segment::class);
    }
}
