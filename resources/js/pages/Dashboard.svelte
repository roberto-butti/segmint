<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import Building2 from 'lucide-svelte/icons/building-2';
    import ChevronDown from 'lucide-svelte/icons/chevron-down';
    import Clock3 from 'lucide-svelte/icons/clock-3';
    import FolderKanban from 'lucide-svelte/icons/folder-kanban';
    import Star from 'lucide-svelte/icons/star';
    import Users from 'lucide-svelte/icons/users';

    import AppHead from '@/components/AppHead.svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import {
        Card,
        CardContent,
        CardHeader,
        CardTitle,
    } from '@/components/ui/card';
    import AppLayout from '@/layouts/AppLayout.svelte';
    import { dashboard } from '@/routes';
    import organizations from '@/routes/organizations';
    import organizationProjects from '@/routes/organizations/projects';
    import projects from '@/routes/projects';
    import type { BreadcrumbItem } from '@/types';

    interface FavoriteProject {
        id: number;
        public_id: string;
        name: string;
    }

    interface RecentlyActiveProject {
        id: number;
        public_id: string;
        name: string;
        last_event_at: string;
        events_count_24h: number;
    }

    interface Organization {
        id: number;
        public_id: string;
        name: string;
        role: string;
        projects_count: number;
        active_projects_count: number;
        members_count: number;
        favorite_projects: FavoriteProject[];
        recently_active_projects: RecentlyActiveProject[];
    }

    let {
        organizations: organizationList,
    }: {
        organizations: Organization[];
    } = $props();

    const ownedOrganization = $derived(
        organizationList.find((organization) => organization.role === 'owner'),
    );
    const otherOrganizations = $derived(
        organizationList.filter(
            (organization) => organization.role !== 'owner',
        ),
    );

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Organizations',
            href: dashboard(),
        },
    ];

    function roleLabel(role: string): string {
        return role.charAt(0).toUpperCase() + role.slice(1);
    }

    function formatActivityTime(value: string): string {
        return new Intl.DateTimeFormat(undefined, {
            dateStyle: 'medium',
            timeStyle: 'short',
        }).format(new Date(value));
    }
</script>

{#snippet organizationPanel(organization: Organization, primary = false)}
    <Card class="w-full overflow-hidden">
        <CardHeader class="border-b bg-muted/30">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex min-w-0 items-center gap-3">
                    <div
                        class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-background shadow-sm ring-1 ring-border"
                    >
                        <Building2 class="size-5 text-primary" />
                    </div>
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <CardTitle class="truncate text-lg">
                                {organization.name}
                            </CardTitle>
                            <Badge variant="secondary">
                                {roleLabel(organization.role)}
                            </Badge>
                            {#if primary}
                                <Badge>Primary organization</Badge>
                            {/if}
                        </div>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Organization overview and project activity
                        </p>
                    </div>
                </div>
                <div class="flex shrink-0 flex-wrap gap-2">
                    <Button variant="outline" size="sm" asChild>
                        {#snippet children(props)}
                            <Link
                                href={organizationProjects.index.url(
                                    organization.public_id,
                                )}
                                class={props.class}
                            >
                                View projects
                            </Link>
                        {/snippet}
                    </Button>
                    <Button size="sm" asChild>
                        {#snippet children(props)}
                            <Link
                                href={organizations.dashboard.url(
                                    organization.public_id,
                                )}
                                class={props.class}
                            >
                                Open dashboard
                            </Link>
                        {/snippet}
                    </Button>
                </div>
            </div>
        </CardHeader>

        <CardContent
            class="grid gap-6 pt-6 lg:grid-cols-[minmax(12rem,0.7fr)_minmax(0,1fr)_minmax(0,1fr)]"
        >
            <section aria-label={`${organization.name} statistics`}>
                <h4 class="mb-3 text-sm font-medium">Overview</h4>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-1">
                    <div class="rounded-lg border p-4">
                        <div
                            class="flex items-center gap-2 text-muted-foreground"
                        >
                            <FolderKanban class="size-4" />
                            <span class="text-sm">Projects</span>
                        </div>
                        <p class="mt-2 text-2xl font-semibold">
                            {organization.projects_count}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {organization.active_projects_count} active
                        </p>
                    </div>
                    <div class="rounded-lg border p-4">
                        <div
                            class="flex items-center gap-2 text-muted-foreground"
                        >
                            <Users class="size-4" />
                            <span class="text-sm">Members</span>
                        </div>
                        <p class="mt-2 text-2xl font-semibold">
                            {organization.members_count}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {organization.members_count === 1
                                ? 'Organization member'
                                : 'Organization members'}
                        </p>
                    </div>
                </div>
            </section>

            <section aria-labelledby={`favorites-${organization.id}`}>
                <div class="mb-3 flex items-center gap-2">
                    <Star class="size-4 fill-current text-amber-500" />
                    <h4
                        id={`favorites-${organization.id}`}
                        class="text-sm font-medium"
                    >
                        Favorite projects
                    </h4>
                </div>
                {#if organization.favorite_projects.length === 0}
                    <div class="rounded-lg border border-dashed p-4">
                        <p class="text-sm text-muted-foreground">
                            No favorite projects in this organization.
                        </p>
                    </div>
                {:else}
                    <div class="space-y-2">
                        {#each organization.favorite_projects as project (project.id)}
                            <Link
                                href={projects.show.url(project.public_id)}
                                class="block rounded-lg border p-3 text-sm transition-colors hover:bg-accent hover:text-accent-foreground"
                            >
                                <span class="block truncate font-medium">
                                    {project.name}
                                </span>
                            </Link>
                        {/each}
                    </div>
                {/if}
            </section>

            <section aria-labelledby={`activity-${organization.id}`}>
                <div class="mb-3 flex items-center gap-2">
                    <Clock3 class="size-4 text-muted-foreground" />
                    <h4
                        id={`activity-${organization.id}`}
                        class="text-sm font-medium"
                    >
                        Recently active
                    </h4>
                </div>
                {#if organization.recently_active_projects.length === 0}
                    <div class="rounded-lg border border-dashed p-4">
                        <p class="text-sm text-muted-foreground">
                            No recent project activity in this organization.
                        </p>
                    </div>
                {:else}
                    <div class="space-y-2">
                        {#each organization.recently_active_projects as project (project.id)}
                            <Link
                                href={projects.show.url(project.public_id)}
                                class="block rounded-lg border p-3 text-sm transition-colors hover:bg-accent hover:text-accent-foreground"
                            >
                                <span class="block truncate font-medium">
                                    {project.name}
                                </span>
                                <span
                                    class="mt-1 block text-xs text-muted-foreground"
                                >
                                    {formatActivityTime(project.last_event_at)}
                                    · {project.events_count_24h}
                                    {project.events_count_24h === 1
                                        ? 'event'
                                        : 'events'} in 24h
                                </span>
                            </Link>
                        {/each}
                    </div>
                {/if}
            </section>
        </CardContent>
    </Card>
{/snippet}

<AppHead title="Organizations" />

<AppLayout {breadcrumbs}>
    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
        <div>
            <h2 class="text-xl font-semibold">Organizations</h2>
            <p class="mt-1 text-sm text-muted-foreground">
                Your organizations, favorite projects, and recent activity.
            </p>
        </div>

        {#if organizationList.length === 0}
            <div
                class="flex flex-1 items-center justify-center rounded-xl border border-dashed border-sidebar-border p-12"
            >
                <div class="text-center">
                    <Building2
                        class="mx-auto mb-3 size-8 text-muted-foreground"
                    />
                    <p class="font-medium">No organizations available</p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Create or join an organization to manage projects.
                    </p>
                </div>
            </div>
        {:else}
            {#if ownedOrganization}
                <section class="space-y-3">
                    <h3 class="text-sm font-medium text-muted-foreground">
                        Owned organization
                    </h3>
                    {@render organizationPanel(ownedOrganization, true)}
                </section>
            {/if}

            {#if otherOrganizations.length > 0}
                <details class="group rounded-xl border bg-card shadow-sm">
                    <summary
                        class="flex cursor-pointer list-none items-center justify-between gap-3 rounded-xl px-5 py-4 font-medium transition-colors hover:bg-muted/50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring [&::-webkit-details-marker]:hidden"
                    >
                        <span>
                            Other organizations ({otherOrganizations.length})
                        </span>
                        <ChevronDown
                            class="size-5 text-muted-foreground transition-transform group-open:rotate-180"
                        />
                    </summary>
                    <div class="space-y-4 border-t p-4">
                        {#each otherOrganizations as organization (organization.id)}
                            {@render organizationPanel(organization)}
                        {/each}
                    </div>
                </details>
            {/if}
        {/if}
    </div>
</AppLayout>
