<?php

namespace Database\Factories;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
        ];
    }

    /**
     * Create the organization with an owner membership.
     */
    public function withOwner(?User $user = null): static
    {
        return $this->afterCreating(function (Organization $organization) use ($user): void {
            $owner = $user ?? User::factory()->create();
            $organization->members()->attach($owner, ['role' => OrganizationRole::Owner->value]);
        });
    }
}
