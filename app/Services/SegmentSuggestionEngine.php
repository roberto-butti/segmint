<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SegmentSuggestionEngine
{
    /**
     * Analyze recent event logs and suggest potential segments.
     *
     * @return Collection<int, array{name: string, description: string, rules: array<int, array{type: string, key: string, operator: string, value: string}>, confidence: string, reason: string}>
     */
    public function suggest(Project $project, int $limit = 100): Collection
    {
        $suggestions = collect();

        $this->suggestFromUtmSources($project, $limit, $suggestions);
        $this->suggestFromUtmCampaigns($project, $limit, $suggestions);
        $this->suggestFromUtmMediums($project, $limit, $suggestions);
        $this->suggestFromTopPages($project, $limit, $suggestions);
        $this->suggestFromReferrers($project, $limit, $suggestions);
        $this->suggestFromReturningVisitors($project, $limit, $suggestions);
        $this->suggestFromFrequentPageVisitors($project, $limit, $suggestions);

        // Annotate suggestions with existing segment matches
        $existingSegments = $project->segments()->with('rules')->get();

        return $suggestions->map(function ($suggestion) use ($existingSegments) {
            $suggestion['status'] = 'new';
            $suggestion['existingSegment'] = null;

            foreach ($suggestion['rules'] as $suggestedRule) {
                foreach ($existingSegments as $segment) {
                    foreach ($segment->rules as $existingRule) {
                        $sameType = $existingRule->type->value === $suggestedRule['type'];
                        $sameKey = $existingRule->key === $suggestedRule['key'];
                        $sameOperator = $existingRule->operator->value === $suggestedRule['operator'];

                        if (! $sameType || ! $sameKey || ! $sameOperator) {
                            continue;
                        }

                        $sameValue = $existingRule->value === $suggestedRule['value'];

                        if ($sameValue) {
                            // Exact rule match — segment exists
                            $suggestion['status'] = 'exists';
                            $suggestion['existingSegment'] = [
                                'id' => $segment->id,
                                'name' => $segment->name,
                                'slug' => $segment->slug,
                            ];

                            return $suggestion;
                        }

                        // Same rule structure, different value — similar
                        if ($suggestion['status'] !== 'exists') {
                            $suggestion['status'] = 'similar';
                            $suggestion['existingSegment'] = [
                                'id' => $segment->id,
                                'name' => $segment->name,
                                'slug' => $segment->slug,
                                'matchingRule' => [
                                    'type' => $existingRule->type->value,
                                    'key' => $existingRule->key,
                                    'operator' => $existingRule->operator->value,
                                    'value' => $existingRule->value,
                                ],
                            ];
                        }
                    }
                }
            }

            return $suggestion;
        })->sortBy(fn ($s) => match ($s['status']) {
            'new' => 0,
            'similar' => 1,
            'exists' => 2,
            default => 3,
        })->values();
    }

    /**
     * Suggest segments based on top UTM sources.
     */
    private function suggestFromUtmSources(Project $project, int $limit, Collection $suggestions): void
    {
        $sources = DB::table('event_logs')
            ->where('project_id', $project->id)
            ->whereNotNull('utm_source')
            ->where('utm_source', '!=', '')
            ->select('utm_source', DB::raw('COUNT(*) as count'))
            ->groupBy('utm_source')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        $total = $project->eventLogs()->count();

        foreach ($sources as $source) {
            $percentage = $total > 0 ? round(($source->count / $total) * 100) : 0;

            $suggestions->push([
                'name' => ucfirst($source->utm_source).' Visitors',
                'slug' => strtolower($source->utm_source).'_visitors',
                'description' => "Visitors arriving from {$source->utm_source} ({$source->count} events, {$percentage}% of traffic)",
                'rules' => [[
                    'type' => 'comparison',
                    'key' => 'utm_source',
                    'operator' => '=',
                    'value' => $source->utm_source,
                ]],
                'confidence' => $percentage >= 10 ? 'high' : ($percentage >= 3 ? 'medium' : 'low'),
                'reason' => "{$source->count} events from utm_source=\"{$source->utm_source}\" ({$percentage}% of all events)",
                'category' => 'Traffic Source',
            ]);
        }
    }

    /**
     * Suggest segments based on UTM campaigns.
     */
    private function suggestFromUtmCampaigns(Project $project, int $limit, Collection $suggestions): void
    {
        $campaigns = DB::table('event_logs')
            ->where('project_id', $project->id)
            ->whereNotNull('utm_campaign')
            ->where('utm_campaign', '!=', '')
            ->select('utm_campaign', DB::raw('COUNT(*) as count'))
            ->groupBy('utm_campaign')
            ->orderByDesc('count')
            ->limit(3)
            ->get();

        $total = $project->eventLogs()->count();

        foreach ($campaigns as $campaign) {
            $percentage = $total > 0 ? round(($campaign->count / $total) * 100) : 0;

            $suggestions->push([
                'name' => ucwords(str_replace(['-', '_'], ' ', $campaign->utm_campaign)).' Campaign',
                'slug' => 'campaign_'.strtolower(preg_replace('/[^a-z0-9]+/', '_', $campaign->utm_campaign)),
                'description' => "Visitors from the \"{$campaign->utm_campaign}\" campaign",
                'rules' => [[
                    'type' => 'comparison',
                    'key' => 'utm_campaign',
                    'operator' => '=',
                    'value' => $campaign->utm_campaign,
                ]],
                'confidence' => $percentage >= 5 ? 'high' : 'medium',
                'reason' => "{$campaign->count} events from campaign \"{$campaign->utm_campaign}\"",
                'category' => 'Campaign',
            ]);
        }
    }

    /**
     * Suggest segments based on UTM mediums.
     */
    private function suggestFromUtmMediums(Project $project, int $limit, Collection $suggestions): void
    {
        $mediums = DB::table('event_logs')
            ->where('project_id', $project->id)
            ->whereNotNull('utm_medium')
            ->where('utm_medium', '!=', '')
            ->select('utm_medium', DB::raw('COUNT(*) as count'))
            ->groupBy('utm_medium')
            ->orderByDesc('count')
            ->limit(3)
            ->get();

        $total = $project->eventLogs()->count();

        foreach ($mediums as $medium) {
            $percentage = $total > 0 ? round(($medium->count / $total) * 100) : 0;

            if ($percentage < 3) {
                continue;
            }

            $suggestions->push([
                'name' => ucfirst($medium->utm_medium).' Traffic',
                'slug' => strtolower($medium->utm_medium).'_traffic',
                'description' => "Visitors arriving via {$medium->utm_medium} medium",
                'rules' => [[
                    'type' => 'comparison',
                    'key' => 'utm_medium',
                    'operator' => '=',
                    'value' => $medium->utm_medium,
                ]],
                'confidence' => $percentage >= 10 ? 'high' : 'medium',
                'reason' => "{$medium->count} events via medium \"{$medium->utm_medium}\" ({$percentage}%)",
                'category' => 'Traffic Source',
            ]);
        }
    }

    /**
     * Suggest segments based on most visited pages.
     */
    private function suggestFromTopPages(Project $project, int $limit, Collection $suggestions): void
    {
        $pages = DB::table('event_logs')
            ->where('project_id', $project->id)
            ->where('event_type', 'page-view')
            ->whereNotNull('page_path')
            ->where('page_path', '!=', '')
            ->select('page_path', DB::raw('COUNT(DISTINCT visitor_id) as unique_visitors'))
            ->groupBy('page_path')
            ->orderByDesc('unique_visitors')
            ->limit(3)
            ->get();

        foreach ($pages as $page) {
            if ($page->unique_visitors < 2) {
                continue;
            }

            $pathName = $page->page_path === '/' ? 'Homepage' : ucwords(str_replace(['/', '-', '_'], [' ', ' ', ' '], trim($page->page_path, '/')));

            $suggestions->push([
                'name' => $pathName.' Visitors',
                'slug' => 'page_'.strtolower(preg_replace('/[^a-z0-9]+/', '_', trim($page->page_path, '/'))),
                'description' => "Visitors who viewed {$page->page_path} ({$page->unique_visitors} unique visitors)",
                'rules' => [[
                    'type' => 'comparison',
                    'key' => 'page_path',
                    'operator' => '=',
                    'value' => $page->page_path,
                ]],
                'confidence' => $page->unique_visitors >= 10 ? 'high' : 'medium',
                'reason' => "{$page->unique_visitors} unique visitors to {$page->page_path}",
                'category' => 'Page',
            ]);
        }
    }

    /**
     * Suggest segments based on referrer domains.
     */
    private function suggestFromReferrers(Project $project, int $limit, Collection $suggestions): void
    {
        $referrers = DB::table('event_logs')
            ->where('project_id', $project->id)
            ->whereNotNull('referrer_url')
            ->where('referrer_url', '!=', '')
            ->select('referrer_url', DB::raw('COUNT(*) as count'))
            ->groupBy('referrer_url')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        // Group by domain
        $domains = collect();
        foreach ($referrers as $ref) {
            $host = parse_url($ref->referrer_url, PHP_URL_HOST);
            if (! $host) {
                continue;
            }

            $existing = $domains->firstWhere('domain', $host);
            if ($existing) {
                $domains = $domains->map(fn ($d) => $d['domain'] === $host
                    ? array_merge($d, ['count' => $d['count'] + $ref->count])
                    : $d
                );
            } else {
                $domains->push(['domain' => $host, 'count' => $ref->count]);
            }
        }

        foreach ($domains->sortByDesc('count')->take(3) as $domain) {
            if ($domain['count'] < 2) {
                continue;
            }

            $cleanName = str_replace('www.', '', $domain['domain']);

            $suggestions->push([
                'name' => 'From '.ucfirst($cleanName),
                'slug' => 'referrer_'.strtolower(preg_replace('/[^a-z0-9]+/', '_', $cleanName)),
                'description' => "Visitors referred from {$cleanName}",
                'rules' => [[
                    'type' => 'comparison',
                    'key' => 'referrer_url',
                    'operator' => 'contains',
                    'value' => $domain['domain'],
                ]],
                'confidence' => $domain['count'] >= 10 ? 'high' : 'medium',
                'reason' => "{$domain['count']} events referred from {$domain['domain']}",
                'category' => 'Referrer',
            ]);
        }
    }

    /**
     * Suggest a returning visitors segment based on visit patterns.
     */
    private function suggestFromReturningVisitors(Project $project, int $limit, Collection $suggestions): void
    {
        $returningVisitors = DB::table('event_logs')
            ->where('project_id', $project->id)
            ->where('event_type', 'page-view')
            ->whereNotNull('visitor_id')
            ->select('visitor_id', DB::raw('COUNT(*) as visits'))
            ->groupBy('visitor_id')
            ->havingRaw('COUNT(*) >= 3')
            ->get();

        if ($returningVisitors->count() >= 1) {
            $avgVisits = round($returningVisitors->avg('visits'));
            $threshold = min($avgVisits, 5);

            $suggestions->push([
                'name' => 'Returning Visitors',
                'slug' => 'returning_visitors',
                'description' => "Visitors with {$threshold}+ page views ({$returningVisitors->count()} visitors match)",
                'rules' => [[
                    'type' => 'visit_count',
                    'key' => 'page-view',
                    'operator' => '>=',
                    'value' => (string) $threshold,
                ]],
                'confidence' => $returningVisitors->count() >= 5 ? 'high' : 'medium',
                'reason' => "{$returningVisitors->count()} visitors have {$threshold}+ page views (avg: {$avgVisits})",
                'category' => 'Engagement',
            ]);
        }
    }

    /**
     * Suggest a frequent page visitor segment if visitors revisit the same page.
     */
    private function suggestFromFrequentPageVisitors(Project $project, int $limit, Collection $suggestions): void
    {
        // Count distinct visitors who viewed any single page 3+ times
        $frequentVisitors = DB::table('event_logs')
            ->where('project_id', $project->id)
            ->where('event_type', 'page-view')
            ->whereNotNull('page_path')
            ->whereNotNull('visitor_id')
            ->select('visitor_id', 'page_path', DB::raw('COUNT(*) as views'))
            ->groupBy('visitor_id', 'page_path')
            ->havingRaw('COUNT(*) >= 3')
            ->get();

        if ($frequentVisitors->isEmpty()) {
            return;
        }

        $uniqueVisitors = $frequentVisitors->pluck('visitor_id')->unique()->count();

        $suggestions->push([
            'name' => 'Frequent Page Visitors',
            'slug' => 'frequent_page_visitors',
            'description' => "Visitors who viewed the same page 3+ times ({$uniqueVisitors} visitors match)",
            'rules' => [[
                'type' => 'page_view_count',
                'key' => '',
                'operator' => '>=',
                'value' => '3',
            ]],
            'confidence' => $uniqueVisitors >= 3 ? 'high' : 'medium',
            'reason' => "{$uniqueVisitors} visitors viewed the same page 3+ times",
            'category' => 'Engagement',
        ]);
    }
}
