<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;

#[Fillable(['slug', 'organization_id', 'name', 'description', 'active'])]
class Project extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::created(function (Project $project): void {
            foreach (RuleTemplate::defaults() as $template) {
                $project->ruleTemplates()->create($template);
            }

            $project->accessTokens()->create([
                'name' => 'Default',
                'token' => Str::random(64),
                'active' => true,
            ]);
        });
    }

    protected function casts(): array
    {
        return [
            'name' => 'string',
            'description' => 'string',
            'active' => 'boolean',
        ];
    }

    public function accessTokens(): HasMany
    {
        return $this->hasMany(AccessToken::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function segments(): HasMany
    {
        return $this->hasMany(Segment::class);
    }

    public function eventLogs(): HasMany
    {
        return $this->hasMany(EventLog::class);
    }

    public function ruleTemplates(): HasMany
    {
        return $this->hasMany(RuleTemplate::class);
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
