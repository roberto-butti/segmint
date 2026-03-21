<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'description', 'active'])]
class Segment extends Model
{
    use HasFactory;

    protected $appends = ['value'];

    protected function casts(): array
    {
        return [
            'name' => 'string',
            'description' => 'string',
            'active' => 'boolean',
        ];
    }

    public function rules(): HasMany
    {
        return $this->hasMany(SegmentRule::class)->orderBy('priority');
    }

    public function getValueAttribute(): string
    {
        return $this->slug ?? '';
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function segmentMatches(): HasMany
    {
        return $this->hasMany(SegmentMatch::class);
    }
}
