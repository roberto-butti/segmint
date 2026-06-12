<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationMembershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_membership_role_is_cast_to_the_organization_role_enum(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $organization->members()->attach($user, ['role' => OrganizationRole::Member->value]);

        $membership = $user->organizations()->firstOrFail()->pivot;

        $this->assertSame(OrganizationRole::Member, $membership->role);
        $this->assertSame(OrganizationRole::Member, $user->roleInOrganization($organization));
    }
}
