<?php

namespace Tests;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    /**
     * Create a user with a personal organization where they are the owner.
     *
     * @param  array<string, mixed>  $userAttributes
     * @return array{user: User, organization: Organization}
     */
    protected function createUserWithOrganization(array $userAttributes = []): array
    {
        $user = User::factory()->create($userAttributes);
        $organization = Organization::factory()->create();
        $organization->members()->attach($user, ['role' => OrganizationRole::Owner->value]);

        return ['user' => $user, 'organization' => $organization];
    }

    protected function skipUnlessFortifyFeature(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
