<?php

namespace Database\Seeders;

use App\Models\AccessToken;
use App\Models\Organization;
use App\Models\Project;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $org1 = Organization::where('slug', 'test-organization')->first();

        $project = Project::firstOrCreate(
            [
                'slug' => 'demo-project',
            ],
            [
                'organization_id' => $org1->id,
                'name' => 'Demo Project',
                'description' => 'This is a demo project',
                'active' => true,
            ],
        );

        // Override the auto-created default token with a known one for testing
        $project->accessTokens()->delete();
        AccessToken::create([
            'project_id' => $project->id,
            'name' => 'Demo API Token',
            'token' => 'this-is-a-test-token',
            'active' => true,
        ]);

        $project = Project::firstOrCreate(
            [
                'slug' => 'demo-project-2',
            ],
            [
                'organization_id' => $org1->id,
                'name' => 'Second Demo Project',
                'description' => 'This is another demo project',
                'active' => true,
            ],
        );

        $project->accessTokens()->delete();
        AccessToken::create([
            'project_id' => $project->id,
            'name' => 'Demo API Token another project',
            'token' => 'this-is-a-test-token-2',
            'active' => true,
        ]);

        $org2 = Organization::where('slug', 'another-organization')->first();

        Project::firstOrCreate(
            [
                'slug' => 'demo-project-user-2',
            ],
            [
                'organization_id' => $org2->id,
                'name' => 'Second Demo Project for user 2',
                'description' => 'This is another demo project for another user',
                'active' => true,
            ],
        );
    }
}
