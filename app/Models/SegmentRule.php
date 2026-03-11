<?php

namespace App\Models;

use App\Services\SegmentRules\SegmentRuleType;
use Illuminate\Database\Eloquent\Model;

class SegmentRule extends Model
{
    protected $fillable = ['segment_id', 'type', 'key', 'operator', 'value', 'priority'];

    protected $casts = [
        'segment_id' => 'integer',
        'type' => SegmentRuleType::class,
        'key' => 'string',
        'operator' => 'string',
        'value' => 'string', // keep as string; your engine will parse as needed
        'priority' => 'integer',
    ];

    public function segment()
    {
        return $this->belongsTo(Segment::class);
    }
}
