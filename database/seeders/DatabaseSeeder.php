<?php

namespace Database\Seeders;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\Project;
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
        // Org 1: Test Organization — user1 owns it
        $org1 = Organization::create([
            'name' => 'Test Organization',
            'slug' => 'test-organization',
        ]);

        $user1 = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'prova123',
            'owned_organization_id' => $org1->id,
        ]);
        $org1->members()->attach($user1, ['role' => OrganizationRole::Admin->value]);

        // Org 2: Another Organization — user2 owns it
        $org2 = Organization::create([
            'name' => 'Another Organization',
            'slug' => 'another-organization',
        ]);

        $user2 = User::factory()->create([
            'name' => 'Another User',
            'email' => 'test1@example.com',
            'password' => 'prova1234',
            'owned_organization_id' => $org2->id,
        ]);
        $org2->members()->attach($user2, ['role' => OrganizationRole::Admin->value]);

        // Org 3: Acme Corp — user1 is admin, user2 is admin (no owner via user table)
        $org3 = Organization::create([
            'name' => 'Acme Corp',
            'slug' => 'acme-corp',
        ]);
        $org3->members()->attach($user1, ['role' => OrganizationRole::Admin->value]);
        $org3->members()->attach($user2, ['role' => OrganizationRole::Admin->value]);

        Project::create([
            'organization_id' => $org3->id,
            'name' => 'Acme Website',
            'slug' => 'acme-website',
            'description' => 'Main marketing website tracking',
            'active' => true,
        ]);

        // Org 4: Startup Inc — user1 is member
        $org4 = Organization::create([
            'name' => 'Startup Inc',
            'slug' => 'startup-inc',
        ]);
        $org4->members()->attach($user1, ['role' => OrganizationRole::Member->value]);

        Project::create([
            'organization_id' => $org4->id,
            'name' => 'Startup App',
            'slug' => 'startup-app',
            'description' => 'Mobile app event tracking',
            'active' => true,
        ]);

        Project::create([
            'organization_id' => $org4->id,
            'name' => 'Startup Landing',
            'slug' => 'startup-landing',
            'description' => 'Landing page A/B testing',
            'active' => true,
        ]);

        // Org 5: Agency Pro — user1 is viewer
        $org5 = Organization::create([
            'name' => 'Agency Pro',
            'slug' => 'agency-pro',
        ]);
        $org5->members()->attach($user1, ['role' => OrganizationRole::Viewer->value]);

        Project::create([
            'organization_id' => $org5->id,
            'name' => 'Client Campaign',
            'slug' => 'client-campaign',
            'description' => 'Campaign tracking for client X',
            'active' => true,
        ]);

        $this->call([ProjectSeeder::class, SegmentSeeder::class]);
    }
}
