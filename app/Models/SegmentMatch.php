<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SegmentMatch extends Model
{
    use HasFactory;

    protected $fillable = ['event_log_id', 'segment_id', 'matched'];

    protected function casts(): array
    {
        return [
            'matched' => 'boolean',
        ];
    }

    public function eventLog(): BelongsTo
    {
        return $this->belongsTo(EventLog::class);
    }

    public function segment(): BelongsTo
    {
        return $this->belongsTo(Segment::class);
    }
}
