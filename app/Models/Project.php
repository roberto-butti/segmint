<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Project extends Model
{
    use HasFactory;

    protected $fillable = ['slug', 'user_id', 'name', 'description', 'active'];

    protected $casts = [
        'name' => 'string',
        'description' => 'string',
        'active' => 'boolean',
    ];

    public function accessTokens()
    {
        return $this->hasMany(AccessToken::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function segments()
    {
        return $this->hasMany(Segment::class);
    }

    public function eventLogs()
    {
        return $this->hasMany(EventLog::class);
    }

    public function segmentMatches(): HasManyThrough
    {
        return $this->hasManyThrough(SegmentMatch::class, Segment::class);
    }

    /**
     * Resolve a project from a plain access token.
     */
    public static function resolveFromAccessToken(string $plainToken): ?self
    {
        return AccessToken::where('token', $plainToken)
            ->where('active', true)
            ->first()
            ->project()
            ->where('active', true)
            ->first();
    }
}
