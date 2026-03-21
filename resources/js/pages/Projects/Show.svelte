<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import AreaChart from '@/components/charts/AreaChart.svelte';
    import BarChart from '@/components/charts/BarChart.svelte';
    import DoughnutChart from '@/components/charts/DoughnutChart.svelte';
    import MultiLineChart from '@/components/charts/MultiLineChart.svelte';
    import { Button } from '@/components/ui/button';
    import {
        Card,
        CardContent,
        CardFooter,
        CardHeader,
        CardTitle,
    } from '@/components/ui/card';
    import AppLayout from '@/layouts/AppLayout.svelte';
    import projects from '@/routes/projects';
    import accessTokens from '@/routes/projects/access-tokens';
    import ruleTemplates from '@/routes/projects/rule-templates';
    import segments from '@/routes/projects/segments';
    import type { BreadcrumbItem } from '@/types';

    interface Project {
        id: number;
        name: string;
        slug: string;
        description: string | null;
        active: boolean;
        created_at: string;
    }

    let {
        project,
        segmentsCount,
        activeSegmentsCount,
        eventLogsCount,
        accessTokensCount,
        ruleTemplatesCount,
        eventsOverTime,
        eventsLastHour,
        segmentMatchesLastHour,
        eventsByType,
        segmentDistribution,
        topSegments,
    }: {
        project: Project;
        segmentsCount: number;
        activeSegmentsCount: number;
        eventLogsCount: number;
        accessTokensCount: number;
        ruleTemplatesCount: number;
        eventsOverTime: Record<string, number>;
        eventsLastHour: Record<string, number>;
        segmentMatchesLastHour: {
            labels: string[];
            datasets: Record<string, number[]>;
        };
        eventsByType: Record<string, number>;
        segmentDistribution: Record<string, number>;
        topSegments: Record<string, number>;
    } = $props();

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Projects',
            href: projects.index.url(),
        },
        {
            title: project.name,
            href: projects.show.url(project.slug),
        },
    ];

    const eventsLabels = $derived(
        Object.keys(eventsOverTime).map((d) => {
            const date = new Date(d);

            return date.toLocaleDateString('en', {
                month: 'short',
                day: 'numeric',
            });
        }),
    );
    const eventsData = $derived(Object.values(eventsOverTime));

    const lastHourLabels = $derived(Object.keys(eventsLastHour));
    const lastHourData = $derived(Object.values(eventsLastHour));
    const hasLastHourEvents = $derived(lastHourData.length > 0);

    const hasSegMatchLastHour = $derived(
        segmentMatchesLastHour.labels.length > 0,
    );

    const eventTypeLabels = $derived(Object.keys(eventsByType));
    const eventTypeData = $derived(Object.values(eventsByType));

    const segmentDistLabels = $derived(Object.keys(segmentDistribution));
    const segmentDistData = $derived(Object.values(segmentDistribution));

    const topSegmentLabels = $derived(Object.keys(topSegments));
    const topSegmentData = $derived(Object.values(topSegments));

    const hasEvents = $derived(eventsData.length > 0);
    const hasSegmentMatches = $derived(segmentDistData.length > 0);
</script>

<AppHead title={project.name} />

<AppLayout {breadcrumbs}>
    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold">{project.name}</h2>
                {#if project.description}
                    <p class="mt-1 text-sm text-muted-foreground">
                        {project.description}
                    </p>
                {/if}
            </div>
            <div class="flex items-center gap-3">
                <span
                    class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium {project.active
                        ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300'
                        : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'}"
                >
                    {project.active ? 'Active' : 'Inactive'}
                </span>
                <Button variant="outline" size="sm">
                    <Link href={projects.edit.url(project.slug)}>Edit</Link>
                </Button>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-3 lg:grid-cols-5">
            <Card>
                <CardHeader>
                    <CardTitle class="text-sm font-medium">Segments</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="text-3xl font-bold">{segmentsCount}</div>
                    <p class="text-xs text-muted-foreground">
                        {activeSegmentsCount} active
                    </p>
                </CardContent>
                <CardFooter>
                    <Button variant="outline" size="sm" class="w-full">
                        <Link href={segments.index.url(project.slug)}
                            >View segments</Link
                        >
                    </Button>
                </CardFooter>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="text-sm font-medium">Event Logs</CardTitle
                    >
                </CardHeader>
                <CardContent>
                    <div class="text-3xl font-bold">{eventLogsCount}</div>
                    <p class="text-xs text-muted-foreground">
                        Total events tracked
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="text-sm font-medium"
                        >Access Tokens</CardTitle
                    >
                </CardHeader>
                <CardContent>
                    <div class="text-3xl font-bold">{accessTokensCount}</div>
                    <p class="text-xs text-muted-foreground">Total tokens</p>
                </CardContent>
                <CardFooter>
                    <Button variant="outline" size="sm" class="w-full">
                        <Link href={accessTokens.index.url(project.slug)}
                            >View access tokens</Link
                        >
                    </Button>
                </CardFooter>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="text-sm font-medium"
                        >Rule Templates</CardTitle
                    >
                </CardHeader>
                <CardContent>
                    <div class="text-3xl font-bold">{ruleTemplatesCount}</div>
                    <p class="text-xs text-muted-foreground">
                        Reusable presets
                    </p>
                </CardContent>
                <CardFooter>
                    <Button variant="outline" size="sm" class="w-full">
                        <Link href={ruleTemplates.index.url(project.slug)}
                            >Manage templates</Link
                        >
                    </Button>
                </CardFooter>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="text-sm font-medium">Status</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="text-3xl font-bold">
                        {project.active ? 'Live' : 'Off'}
                    </div>
                    <p class="text-xs text-muted-foreground">
                        Project is {project.active
                            ? 'receiving events'
                            : 'not active'}
                    </p>
                </CardContent>
            </Card>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle class="text-sm font-medium"
                        >Segment distribution</CardTitle
                    >
                </CardHeader>
                <CardContent>
                    {#if hasSegmentMatches}
                        <DoughnutChart
                            labels={segmentDistLabels}
                            data={segmentDistData}
                        />
                    {:else}
                        <div class="flex h-64 items-center justify-center">
                            <p class="text-sm text-muted-foreground">
                                No segment matches yet
                            </p>
                        </div>
                    {/if}
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="text-sm font-medium"
                        >Segment matches — last hour</CardTitle
                    >
                </CardHeader>
                <CardContent>
                    {#if hasSegMatchLastHour}
                        <MultiLineChart
                            labels={segmentMatchesLastHour.labels}
                            datasets={segmentMatchesLastHour.datasets}
                        />
                    {:else}
                        <div class="flex h-64 items-center justify-center">
                            <p class="text-sm text-muted-foreground">
                                No segment matches in the last hour
                            </p>
                        </div>
                    {/if}
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="text-sm font-medium"
                        >Top segments by matches</CardTitle
                    >
                </CardHeader>
                <CardContent>
                    {#if topSegmentLabels.length > 0}
                        <BarChart
                            labels={topSegmentLabels}
                            data={topSegmentData}
                            label="Matches"
                            horizontal
                        />
                    {:else}
                        <div class="flex h-64 items-center justify-center">
                            <p class="text-sm text-muted-foreground">
                                No segment matches yet
                            </p>
                        </div>
                    {/if}
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="text-sm font-medium"
                        >Events by type</CardTitle
                    >
                </CardHeader>
                <CardContent>
                    {#if eventTypeLabels.length > 0}
                        <BarChart
                            labels={eventTypeLabels}
                            data={eventTypeData}
                            label="Events"
                        />
                    {:else}
                        <div class="flex h-64 items-center justify-center">
                            <p class="text-sm text-muted-foreground">
                                No events tracked yet
                            </p>
                        </div>
                    {/if}
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="text-sm font-medium"
                        >Events — last hour</CardTitle
                    >
                </CardHeader>
                <CardContent>
                    {#if hasLastHourEvents}
                        <AreaChart
                            labels={lastHourLabels}
                            data={lastHourData}
                            label="Events"
                        />
                    {:else}
                        <div class="flex h-64 items-center justify-center">
                            <p class="text-sm text-muted-foreground">
                                No events in the last hour
                            </p>
                        </div>
                    {/if}
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="text-sm font-medium"
                        >Events — last 30 days</CardTitle
                    >
                </CardHeader>
                <CardContent>
                    {#if hasEvents}
                        <AreaChart
                            labels={eventsLabels}
                            data={eventsData}
                            label="Events"
                        />
                    {:else}
                        <div class="flex h-64 items-center justify-center">
                            <p class="text-sm text-muted-foreground">
                                No events in the last 30 days
                            </p>
                        </div>
                    {/if}
                </CardContent>
            </Card>
        </div>
    </div>
</AppLayout>
