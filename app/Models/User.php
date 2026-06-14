<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\OrganizationRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

#[Fillable(['name', 'email', 'password', 'owned_organization_id'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'organization_memberships')
            ->using(OrganizationMembership::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function favoriteProjects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'favorite_projects')
            ->withTimestamps();
    }

    public function assignedProjects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_memberships')
            ->withTimestamps();
    }

    /**
     * Get the organization this user owns (max 1).
     */
    public function ownedOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'owned_organization_id');
    }

    /**
     * Check if the user is the owner of the given organization.
     */
    public function isOwnerOf(Organization $organization): bool
    {
        return $this->owned_organization_id === $organization->id;
    }

    /**
     * Get all projects accessible to this user across all organizations.
     */
    public function accessibleProjects(): Builder
    {
        $allProjectOrganizationIds = $this->organizations()
            ->get()
            ->filter(fn (Organization $organization) => $organization->pivot->role->canAccessAllProjects())
            ->pluck('id');

        return Project::query()
            ->whereIn('organization_id', $allProjectOrganizationIds)
            ->orWhereHas('members', fn (Builder $query) => $query->whereKey($this->id));
    }

    /**
     * Check if the user belongs to the given organization.
     */
    public function belongsToOrganization(Organization $organization): bool
    {
        return $this->organizations()->where('organizations.id', $organization->id)->exists();
    }

    /**
     * Get the user's role in the given organization.
     */
    public function roleInOrganization(Organization $organization): ?OrganizationRole
    {
        $membership = $this->organizations()
            ->where('organizations.id', $organization->id)
            ->first();

        if (! $membership) {
            return null;
        }

        return $membership->pivot->role;
    }

    public function canManageOrganization(Organization $organization): bool
    {
        return $this->isOwnerOf($organization)
            || $this->roleInOrganization($organization)?->canManageOrganization() === true;
    }

    public static function me(): self
    {
        return Auth::user();
    }
}
