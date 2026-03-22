<?php

namespace Tests\Feature;

use App\Models\EventLog;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventLogIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $project = Project::factory()->create();

        $this->get(route('projects.events.index', $project))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_events(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);

        EventLog::create([
            'project_id' => $project->id,
            'uuid' => uniqid('', true),
            'visitor_id' => 'visitor-1',
            'event_type' => 'page-view',
            'page_path' => '/home',
        ]);

        $this->actingAs($user);

        $response = $this->get(route('projects.events.index', $project));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('EventLogs/Index')
            ->where('project.id', $project->id)
            ->where('eventLogs.total', 1)
        );
    }

    public function test_user_cannot_view_events_for_another_users_project(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $otherProject = Project::factory()->create();

        $this->actingAs($user);

        $this->get(route('projects.events.index', $otherProject))
            ->assertForbidden();
    }

    public function test_events_can_be_filtered_by_event_type(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);

        EventLog::create([
            'project_id' => $project->id,
            'uuid' => uniqid('', true),
            'event_type' => 'page-view',
        ]);

        EventLog::create([
            'project_id' => $project->id,
            'uuid' => uniqid('', true),
            'event_type' => 'add-to-cart',
        ]);

        $this->actingAs($user);

        $response = $this->get(route('projects.events.index', [$project, 'event_type' => 'page-view']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('eventLogs.total', 1)
        );
    }

    public function test_events_can_be_filtered_by_search(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);

        EventLog::create([
            'project_id' => $project->id,
            'uuid' => uniqid('', true),
            'event_type' => 'page-view',
            'utm_source' => 'google',
        ]);

        EventLog::create([
            'project_id' => $project->id,
            'uuid' => uniqid('', true),
            'event_type' => 'page-view',
            'utm_source' => 'facebook',
        ]);

        $this->actingAs($user);

        $response = $this->get(route('projects.events.index', [$project, 'search' => 'google']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('eventLogs.total', 1)
        );
    }

    public function test_events_are_paginated(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);

        for ($i = 0; $i < 30; $i++) {
            EventLog::create([
                'project_id' => $project->id,
                'uuid' => uniqid('', true),
                'event_type' => 'page-view',
            ]);
        }

        $this->actingAs($user);

        $response = $this->get(route('projects.events.index', $project));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('eventLogs.total', 30)
            ->where('eventLogs.per_page', 25)
            ->where('eventLogs.last_page', 2)
        );
    }

    public function test_filter_options_are_provided(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);

        EventLog::create([
            'project_id' => $project->id,
            'uuid' => uniqid('', true),
            'event_type' => 'page-view',
            'utm_source' => 'google',
        ]);

        $this->actingAs($user);

        $response = $this->get(route('projects.events.index', $project));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('eventTypes', 1)
            ->has('utmSources', 1)
            ->has('filters')
        );
    }
}
