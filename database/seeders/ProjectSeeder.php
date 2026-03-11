<?php

namespace Database\Seeders;

use App\Models\AccessToken;
use App\Models\Project;
use App\Models\Segment;
use App\Models\SegmentRule;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::where("email", "test@example.com")->first();

        //dd($user);
        /*
        |--------------------------------------------------------------------------
        | Create Project
        |--------------------------------------------------------------------------
        */
        $project = Project::firstOrCreate(
            [
                "slug" => "demo-project",
            ],
            [
                "user_id" => $user->id,
                "name" => "Demo Project",
                "description" => "This is a demo project",
                "active" => true,
            ],
        );

        /*
        |--------------------------------------------------------------------------
        | Create Access Token
        |--------------------------------------------------------------------------
        */
        $plainToken = "this-is-a-test-token";

        AccessToken::firstOrCreate(
            [
                "project_id" => $project->id,
            ],
            [
                "name" => "Demo API Token",
                "token" => $plainToken,
                "active" => true,
            ],
        );

        $project = Project::firstOrCreate(
            [
                "slug" => "demo-project-2",
            ],
            [
                "user_id" => $user->id,
                "name" => "Second Demo Project",
                "description" => "This is another demo project",
                "active" => true,
            ],
        );

        $plainToken = "this-is-a-test-token-2";

        AccessToken::firstOrCreate(
            [
                "project_id" => $project->id,
            ],
            [
                "name" => "Demo API Token another project",
                "token" => $plainToken,
                "active" => true,
            ],
        );

        $user = User::where("email", "test1@example.com")->first();

        $project = Project::firstOrCreate(
            [
                "slug" => "demo-project-user-2",
            ],
            [
                "user_id" => $user->id,
                "name" => "Second Demo Project for user 2",
                "description" =>
                    "This is another demo project for another user",
                "active" => true,
            ],
        );
    }
}
