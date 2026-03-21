<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Segment;
use App\Models\SegmentRule;
use Illuminate\Database\Seeder;

class SegmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $project = Project::resolveFromAccessToken('this-is-a-test-token');

        $google = Segment::create([
            'project_id' => $project->id,
            'name' => 'Google Visitors',
            'slug' => 'google_visitors',
            'description' => 'Users who arrive from Google UTM source',
        ]);

        SegmentRule::create([
            'segment_id' => $google->id,
            'type' => 'comparison',
            'key' => 'utms.utm_source',
            'operator' => '=',
            'value' => 'google',
        ]);

        $italy = Segment::create([
            'project_id' => $project->id,
            'name' => 'Italy Traffic',
            'slug' => 'italy_traffic',
            'description' => 'Visitors from Italy (GeoIP)',
        ]);

        SegmentRule::create([
            'segment_id' => $italy->id,
            'type' => 'browser_language',
            'key' => 'country',
            'operator' => '=',
            'value' => 'it',
        ]);

        $highIntent = Segment::create([
            'project_id' => $project->id,
            'name' => 'High Intent',
            'slug' => 'high_intent',
            'description' => 'Visitors with more than 3 pageviews',
        ]);

        SegmentRule::create([
            'segment_id' => $highIntent->id,
            'type' => 'visit_count',
            'key' => 'visits_count',
            'operator' => '>=',
            'value' => 3,
        ]);

        $campaign = Segment::create([
            'project_id' => $project->id,
            'name' => 'Campaign A Visitors',
            'slug' => 'campaign_visitors',
            'description' => 'Users from campaign "summer-2025"',
        ]);

        SegmentRule::create([
            'segment_id' => $campaign->id,
            'type' => 'comparison',
            'key' => 'utms.utm_campaign',
            'operator' => '=',
            'value' => 'summer-2025',
        ]);

        $tech = Segment::create([
            'project_id' => $project->id,
            'name' => 'Tech Interest',
            'slug' => 'tech_interest',
            'description' => 'People who visit pages containing "ai" or "tech"',
        ]);

        SegmentRule::create([
            'segment_id' => $tech->id,
            'type' => 'comparison',
            'key' => 'last_url',
            'operator' => 'contains',
            'value' => 'ai',
        ]);
    }
}
