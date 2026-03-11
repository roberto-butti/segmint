<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Segment extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'description', 'active'];

    protected $casts = [
        'name' => 'string',
        'description' => 'string',
        'active' => 'boolean',
    ];

    protected $appends = ['value'];

    public function rules()
    {
        return $this->hasMany(SegmentRule::class)->orderBy('priority');
    }

    public function getValueAttribute(): string
    {
        return $this->slug ?? '';
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function segmentMatches(): HasMany
    {
        return $this->hasMany(SegmentMatch::class);
    }
}
