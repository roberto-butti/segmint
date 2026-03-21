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
    'event_properties',
    'metadata',
    'navigation_info',
    'utms',
])]
class EventLog extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'event_properties' => 'array',
            'metadata' => 'array',
            'navigation_info' => 'array',
            'utms' => 'array',
        ];
    }

    public function segmentMatches(): HasMany
    {
        return $this->hasMany(SegmentMatch::class);
    }
}
