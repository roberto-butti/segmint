<script lang="ts">
    import { Link, router } from '@inertiajs/svelte';
    import Activity from 'lucide-svelte/icons/activity';
    import CircleCheck from 'lucide-svelte/icons/circle-check';
    import CircleDashed from 'lucide-svelte/icons/circle-dashed';
    import { untrack } from 'svelte';

    import AppHead from '@/components/AppHead.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import {
        Select,
        SelectContent,
        SelectItem,
        SelectTrigger,
    } from '@/components/ui/select';
    import AppLayout from '@/layouts/AppLayout.svelte';
    import type { BreadcrumbOrganization } from '@/lib/breadcrumbs';
    import { projectBreadcrumbs } from '@/lib/breadcrumbs';
    import events from '@/routes/projects/events';
    import segments from '@/routes/projects/segments';
    import type { BreadcrumbItem } from '@/types';

    interface Project {
        id: number;
        name: string;
        public_id: string;
    }

    interface EventLog {
        id: number;
        visitor_id: string | null;
        event_type: string | null;
        page_url: string | null;
        page_path: string | null;
        referrer_url: string | null;
        utm_source: string | null;
        utm_medium: string | null;
        utm_campaign: string | null;
        created_at: string;
    }

    interface PaginatedData {
        data: EventLog[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        links: { url: string | null; label: string; active: boolean }[];
    }

    interface Filters {
        search: string;
        event_type: string;
        visitor_id: string;
        utm_source: string;
        page_path: string;
    }

    interface TrackingReadiness {
        has_active_token: boolean;
        has_active_segment_with_rules: boolean;
    }

    let {
        project,
        organization,
        eventLogs,
        eventTypes,
        utmSources,
        trackingReadiness,
        filters,
    }: {
        project: Project;
        organization: BreadcrumbOrganization;
        eventLogs: PaginatedData;
        eventTypes: string[];
        utmSources: string[];
        trackingReadiness: TrackingReadiness;
        filters: Filters;
    } = $props();

    let search = $state(untrack(() => filters.search));
    let eventTypeFilter = $state(untrack(() => filters.event_type));
    let utmSourceFilter = $state(untrack(() => filters.utm_source));

    const breadcrumbs: BreadcrumbItem[] = $derived([
        ...projectBreadcrumbs(organization, project),
        { title: 'Events', href: events.index.url(project.public_id) },
    ]);

    function applyFilters(): void {
        const params: Record<string, string> = {};

        if (search.trim()) {
            params.search = search.trim();
        }

        if (eventTypeFilter) {
            params.event_type = eventTypeFilter;
        }

        if (utmSourceFilter) {
            params.utm_source = utmSourceFilter;
        }

        router.get(events.index.url(project.public_id), params, {
            preserveState: true,
        });
    }

    function clearFilters(): void {
        search = '';
        eventTypeFilter = '';
        utmSourceFilter = '';
        router.get(
            events.index.url(project.public_id),
            {},
            { preserveState: false },
        );
    }

    function filterByVisitor(visitorId: string): void {
        router.get(
            events.index.url(project.public_id),
            { visitor_id: visitorId },
            { preserveState: false },
        );
    }

    const hasActiveFilters = $derived(
        filters.search !== '' ||
            filters.event_type !== '' ||
            filters.visitor_id !== '' ||
            filters.utm_source !== '' ||
            filters.page_path !== '',
    );

    function formatDate(dateStr: string): string {
        const d = new Date(dateStr);

        return (
            d.toLocaleDateString('en', {
                month: 'short',
                day: 'numeric',
                year: 'numeric',
            }) +
            ' ' +
            d.toLocaleTimeString('en', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
            })
        );
    }

    function truncate(str: string | null, len: number): string {
        if (!str) {
            return '—';
        }

        return str.length > len ? str.slice(0, len) + '...' : str;
    }

    function paginationLabel(label: string): string {
        return label.replace('&laquo;', '\u00AB').replace('&raquo;', '\u00BB');
    }
</script>

{#snippet eventIcon()}
    <Activity class="size-8" />
{/snippet}

{#snippet clearFiltersAction()}
    <Button size="sm" onclick={clearFilters}>Clear filters</Button>
{/snippet}

{#snippet trackingReadinessDetails()}
    <div
        class="w-full max-w-lg space-y-3 rounded-lg border bg-muted/30 p-4 text-left"
    >
        <p class="text-sm font-medium">Tracking readiness</p>
        <div class="flex items-start gap-3">
            {#if trackingReadiness.has_active_token}
                <CircleCheck class="mt-0.5 size-4 shrink-0 text-green-600" />
            {:else}
                <CircleDashed
                    class="mt-0.5 size-4 shrink-0 text-muted-foreground"
                />
            {/if}
            <div>
                <p class="text-sm font-medium">
                    {trackingReadiness.has_active_token
                        ? 'Active access token is ready'
                        : 'No active access token'}
                </p>
                <p class="text-xs text-muted-foreground">
                    The SDK or tracking API uses an active token to identify
                    this project.
                </p>
            </div>
        </div>
        <div class="flex items-start gap-3">
            <CircleDashed
                class="mt-0.5 size-4 shrink-0 text-muted-foreground"
            />
            <div>
                <p class="text-sm font-medium">Waiting for the first event</p>
                <p class="text-xs text-muted-foreground">
                    Events are created automatically when your application sends
                    activity through the SDK or tracking API.
                </p>
            </div>
        </div>
        <div class="flex items-start gap-3">
            {#if trackingReadiness.has_active_segment_with_rules}
                <CircleCheck class="mt-0.5 size-4 shrink-0 text-green-600" />
            {:else}
                <CircleDashed
                    class="mt-0.5 size-4 shrink-0 text-muted-foreground"
                />
            {/if}
            <div>
                <p class="text-sm font-medium">
                    {trackingReadiness.has_active_segment_with_rules
                        ? 'Audience matching is configured'
                        : 'Audience matching is not configured'}
                </p>
                <p class="text-xs text-muted-foreground">
                    An active segment with rules is optional for tracking, but
                    required to match visitors into an audience.
                </p>
            </div>
        </div>
    </div>
{/snippet}

<AppHead title={`Events - ${project.name}`} />

<AppLayout {breadcrumbs}>
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold">
                Events
                <span class="text-sm font-normal text-muted-foreground"
                    >({eventLogs.total} total)</span
                >
            </h2>
            <Button variant="outline" size="sm">
                <Link href={segments.suggestions.url(project.public_id)}>
                    Suggest segments
                </Link>
            </Button>
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap items-end gap-3">
            <div class="w-64">
                <Input
                    placeholder="Search events..."
                    bind:value={search}
                    onkeydown={(e) => {
                        if (e.key === 'Enter') {
                            applyFilters();
                        }
                    }}
                />
            </div>

            {#if eventTypes.length > 0}
                <div class="w-48">
                    <Select
                        type="single"
                        value={eventTypeFilter}
                        onValueChange={(v) => {
                            eventTypeFilter = v ?? '';
                            applyFilters();
                        }}
                    >
                        <SelectTrigger class="w-full">
                            {eventTypeFilter || 'All event types'}
                        </SelectTrigger>
                        <SelectContent>
                            {#each eventTypes as et (et)}
                                <SelectItem value={et}>{et}</SelectItem>
                            {/each}
                        </SelectContent>
                    </Select>
                </div>
            {/if}

            {#if utmSources.length > 0}
                <div class="w-48">
                    <Select
                        type="single"
                        value={utmSourceFilter}
                        onValueChange={(v) => {
                            utmSourceFilter = v ?? '';
                            applyFilters();
                        }}
                    >
                        <SelectTrigger class="w-full">
                            {utmSourceFilter || 'All UTM sources'}
                        </SelectTrigger>
                        <SelectContent>
                            {#each utmSources as src (src)}
                                <SelectItem value={src}>{src}</SelectItem>
                            {/each}
                        </SelectContent>
                    </Select>
                </div>
            {/if}

            <Button variant="outline" size="sm" onclick={applyFilters}>
                Filter
            </Button>

            {#if hasActiveFilters}
                <Button variant="ghost" size="sm" onclick={clearFilters}>
                    Clear
                </Button>
            {/if}

            {#if filters.visitor_id}
                <span
                    class="rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary"
                >
                    Visitor: {truncate(filters.visitor_id, 12)}
                    <button class="ml-1" onclick={clearFilters}>&times;</button>
                </span>
            {/if}
        </div>

        <!-- Table -->
        {#if eventLogs.data.length === 0}
            <EmptyState
                icon={eventIcon}
                title={hasActiveFilters
                    ? 'No events match your filters'
                    : `No events tracked for ${project.name}`}
                description={hasActiveFilters
                    ? 'Change or clear the current filters to see other tracked events.'
                    : 'Events are activity records sent automatically by your application through the Segmint SDK or tracking API.'}
                class="flex-1"
                details={hasActiveFilters
                    ? undefined
                    : trackingReadinessDetails}
                actions={hasActiveFilters ? clearFiltersAction : undefined}
            />
        {:else}
            <div class="overflow-x-auto rounded-lg border">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50">
                        <tr>
                            <th
                                class="px-4 py-3 text-left font-medium text-muted-foreground"
                                >Time</th
                            >
                            <th
                                class="px-4 py-3 text-left font-medium text-muted-foreground"
                                >Type</th
                            >
                            <th
                                class="px-4 py-3 text-left font-medium text-muted-foreground"
                                >Visitor</th
                            >
                            <th
                                class="px-4 py-3 text-left font-medium text-muted-foreground"
                                >Page</th
                            >
                            <th
                                class="px-4 py-3 text-left font-medium text-muted-foreground"
                                >UTM Source</th
                            >
                            <th
                                class="px-4 py-3 text-left font-medium text-muted-foreground"
                                >Campaign</th
                            >
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        {#each eventLogs.data as log (log.id)}
                            <tr class="hover:bg-muted/30">
                                <td
                                    class="whitespace-nowrap px-4 py-3 text-xs text-muted-foreground"
                                >
                                    {formatDate(log.created_at)}
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        class="rounded bg-muted px-1.5 py-0.5 text-xs font-medium"
                                    >
                                        {log.event_type ?? '—'}
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-mono text-xs">
                                    {#if log.visitor_id}
                                        <button
                                            class="text-primary hover:underline"
                                            onclick={() =>
                                                filterByVisitor(
                                                    log.visitor_id!,
                                                )}
                                        >
                                            {truncate(log.visitor_id, 12)}
                                        </button>
                                    {:else}
                                        <span class="text-muted-foreground"
                                            >—</span
                                        >
                                    {/if}
                                </td>
                                <td
                                    class="max-w-48 truncate px-4 py-3 text-xs"
                                    title={log.page_path ?? ''}
                                >
                                    {log.page_path ?? '—'}
                                </td>
                                <td class="px-4 py-3 text-xs">
                                    {log.utm_source ?? '—'}
                                </td>
                                <td class="px-4 py-3 text-xs">
                                    {log.utm_campaign ?? '—'}
                                </td>
                            </tr>
                        {/each}
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            {#if eventLogs.last_page > 1}
                <div class="flex items-center justify-between">
                    <p class="text-xs text-muted-foreground">
                        Page {eventLogs.current_page} of {eventLogs.last_page}
                    </p>
                    <div class="flex gap-1">
                        {#each eventLogs.links as link (link.label)}
                            {#if link.url}
                                <Link
                                    href={link.url}
                                    class="rounded px-3 py-1 text-xs {link.active
                                        ? 'bg-primary text-primary-foreground'
                                        : 'bg-muted hover:bg-muted/80'}"
                                >
                                    {paginationLabel(link.label)}
                                </Link>
                            {:else}
                                <span
                                    class="rounded px-3 py-1 text-xs text-muted-foreground"
                                >
                                    {paginationLabel(link.label)}
                                </span>
                            {/if}
                        {/each}
                    </div>
                </div>
            {/if}
        {/if}
    </div>
</AppLayout>
