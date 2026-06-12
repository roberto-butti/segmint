<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Segment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectBreadcrumbContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_pages_receive_organization_context_for_breadcrumbs(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createUserWithOrganization();
        $project = Project::factory()->create(['organization_id' => $organization->id]);
        $segment = Segment::factory()->create(['project_id' => $project->id]);

        $pages = [
            [route('projects.show', $project), 'Projects/Show'],
            [route('projects.edit', $project), 'Projects/Edit'],
            [route('projects.segments.index', $project), 'Segments/Index'],
            [route('projects.segments.create', $project), 'Segments/Create'],
            [route('projects.segments.suggestions', $project), 'Segments/Suggestions'],
            [route('projects.segments.show', [$project, $segment]), 'Segments/Show'],
            [route('projects.segments.edit', [$project, $segment]), 'Segments/Edit'],
            [route('projects.rule-templates.index', $project), 'RuleTemplates/Index'],
            [route('projects.events.index', $project), 'EventLogs/Index'],
            [route('projects.access-tokens.index', $project), 'AccessTokens/Index'],
        ];

        foreach ($pages as [$url, $component]) {
            $this->actingAs($user)
                ->get($url)
                ->assertOk()
                ->assertInertia(fn ($page) => $page
                    ->component($component)
                    ->where('organization.id', $organization->id)
                    ->where('organization.public_id', $organization->public_id)
                    ->where('organization.name', $organization->name)
                );
        }
    }
}
