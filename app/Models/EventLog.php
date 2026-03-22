<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'session_id',
    'project_id',
    'uuid',
    'visitor_id',
    'event_type',
    'page_url',
    'page_path',
    'referrer_url',
    'utm_source',
    'utm_medium',
    'utm_campaign',
    'utm_term',
    'utm_content',
    'event_properties',
    'metadata',
])]
class EventLog extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'event_properties' => 'array',
            'metadata' => 'array',
        ];
    }

    public function segmentMatches(): HasMany
    {
        return $this->hasMany(SegmentMatch::class);
    }
}
