<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\OrganizationRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

#[Fillable(['name', 'email', 'password'])]
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
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Get the user's current organization (from session, or first).
     */
    public function currentOrganization(): ?Organization
    {
        $sessionOrgId = session('current_organization_id');

        if ($sessionOrgId) {
            $org = $this->organizations()->where('organizations.id', $sessionOrgId)->first();
            if ($org) {
                return $org;
            }
        }

        return $this->organizations()->first();
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

        return OrganizationRole::from($membership->pivot->role);
    }

    public static function me(): self
    {
        return Auth::user();
    }
}
