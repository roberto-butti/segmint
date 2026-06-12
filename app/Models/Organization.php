<?php

namespace App\Models;

use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['name', 'slug'])]
class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (Organization $organization): void {
            $organization->public_id ??= self::generateUniquePublicId();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'organization_memberships')
            ->using(OrganizationMembership::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    private static function generateUniquePublicId(): string
    {
        do {
            $publicId = Str::random(12);
        } while (self::where('public_id', $publicId)->exists());

        return $publicId;
    }
}
