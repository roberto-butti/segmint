<?php

namespace App\Models;

use App\Enums\OrganizationRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

#[Fillable(['organization_id', 'invited_by_id', 'email', 'role', 'expires_at', 'accepted_at', 'declined_at', 'revoked_at'])]
class OrganizationInvitation extends Model
{
    protected static function booted(): void
    {
        static::creating(function (OrganizationInvitation $invitation): void {
            $invitation->public_id ??= Str::random(32);
        });
    }

    protected function casts(): array
    {
        return [
            'role' => OrganizationRole::class,
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'declined_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_id');
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'organization_invitation_projects')
            ->withPivot('role')
            ->withTimestamps();
    }

    protected function pending(): Attribute
    {
        return Attribute::get(fn () => $this->accepted_at === null
            && $this->declined_at === null
            && $this->revoked_at === null
            && $this->expires_at->isFuture());
    }
}
