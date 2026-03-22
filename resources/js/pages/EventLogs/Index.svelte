<script lang="ts">
    import { Link, router } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import {
        Select,
        SelectContent,
        SelectItem,
        SelectTrigger,
    } from '@/components/ui/select';
    import AppLayout from '@/layouts/AppLayout.svelte';
    import projects from '@/routes/projects';
    import events from '@/routes/projects/events';
    import type { BreadcrumbItem } from '@/types';

    interface Project {
        id: number;
        name: string;
        slug: string;
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

    let {
        project,
        eventLogs,
        eventTypes,
        utmSources,
        filters,
    }: {
        project: Project;
        eventLogs: PaginatedData;
        eventTypes: string[];
        utmSources: string[];
        filters: Filters;
    } = $props();

    let search = $state(filters.search);
    let eventTypeFilter = $state(filters.event_type);
    let utmSourceFilter = $state(filters.utm_source);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Projects', href: projects.index.url() },
        { title: project.name, href: projects.show.url(project.slug) },
        { title: 'Events', href: events.index.url(project.slug) },
    ];

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

        router.get(events.index.url(project.slug), params, {
            preserveState: true,
        });
    }

    function clearFilters(): void {
        search = '';
        eventTypeFilter = '';
        utmSourceFilter = '';
        router.get(
            events.index.url(project.slug),
            {},
            { preserveState: false },
        );
    }

    function filterByVisitor(visitorId: string): void {
        router.get(
            events.index.url(project.slug),
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
            <div
                class="flex flex-1 items-center justify-center rounded-xl border border-dashed border-sidebar-border p-12"
            >
                <p class="text-muted-foreground">
                    {hasActiveFilters
                        ? 'No events match your filters.'
                        : 'No events tracked yet.'}
                </p>
            </div>
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
