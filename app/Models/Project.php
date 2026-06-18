<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;

#[Fillable(['organization_id', 'name', 'description', 'active'])]
class Project extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (Project $project): void {
            $project->public_id ??= self::generateUniquePublicId();
        });

        static::created(function (Project $project): void {
            foreach (RuleTemplate::defaults() as $template) {
                $project->ruleTemplates()->create($template);
            }

            $project->accessTokens()->create([
                'name' => 'Default',
                'token' => AccessToken::generateToken(),
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

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function accessTokens(): HasMany
    {
        return $this->hasMany(AccessToken::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function favoritedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorite_projects')
            ->withTimestamps();
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_memberships')
            ->using(ProjectMembership::class)
            ->withPivot('role')
            ->withTimestamps();
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
    public static function resolveFromAccessToken(string $plainToken, bool $markAsUsed = true): ?self
    {
        $accessToken = AccessToken::query()
            ->with('project')
            ->where('token', $plainToken)
            ->where('active', true)
            ->whereHas('project', fn ($query) => $query->where('active', true))
            ->first();

        if (! $accessToken) {
            return null;
        }

        if ($markAsUsed) {
            $accessToken->markAsUsed();
        }

        return $accessToken->project;
    }

    private static function generateUniquePublicId(): string
    {
        do {
            $publicId = Str::random(12);
        } while (self::where('public_id', $publicId)->exists());

        return $publicId;
    }
}
