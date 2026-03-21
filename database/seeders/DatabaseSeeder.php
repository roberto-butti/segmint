<?php

namespace Database\Seeders;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user1 = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'prova123',
        ]);

        $org1 = Organization::create([
            'name' => 'Test Organization',
            'slug' => 'test-organization',
        ]);
        $org1->members()->attach($user1, ['role' => OrganizationRole::Owner->value]);

        $user2 = User::factory()->create([
            'name' => 'Another User',
            'email' => 'test1@example.com',
            'password' => 'prova1234',
        ]);

        $org2 = Organization::create([
            'name' => 'Another Organization',
            'slug' => 'another-organization',
        ]);
        $org2->members()->attach($user2, ['role' => OrganizationRole::Owner->value]);

        $this->call([ProjectSeeder::class, SegmentSeeder::class]);
    }
}
