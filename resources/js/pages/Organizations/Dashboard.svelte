<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import Activity from 'lucide-svelte/icons/activity';
    import FolderKanban from 'lucide-svelte/icons/folder-kanban';
    import Target from 'lucide-svelte/icons/target';
    import Users from 'lucide-svelte/icons/users';
    import AppHead from '@/components/AppHead.svelte';
    import AreaChart from '@/components/charts/AreaChart.svelte';
    import DoughnutChart from '@/components/charts/DoughnutChart.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import {
        Card,
        CardContent,
        CardFooter,
        CardHeader,
        CardTitle,
    } from '@/components/ui/card';
    import AppLayout from '@/layouts/AppLayout.svelte';
    import { dashboard } from '@/routes';
    import organizations from '@/routes/organizations';
    import organizationProjects from '@/routes/organizations/projects';
    import projectsRoutes from '@/routes/projects';
    import type { BreadcrumbItem } from '@/types';

    interface Organization {
        id: number;
        public_id: string;
        name: string;
    }

    interface Project {
        id: number;
        public_id: string;
        name: string;
        description: string | null;
        active: boolean;
        segments_count: number;
        event_logs_count: number;
    }

    interface Stats {
        members_count: number;
        projects_count: number;
        active_projects_count: number;
        segments_count: number;
        active_segments_count: number;
        events_count: number;
        unique_visitors_count: number;
    }

    let {
        organization,
        currentUserRole,
        canManageProjects,
        stats,
        eventsOverTime,
        roleCounts,
        projects,
    }: {
        organization: Organization;
        currentUserRole: { value: string; label: string };
        canManageProjects: boolean;
        stats: Stats;
        eventsOverTime: Record<string, number>;
        roleCounts: Record<string, number>;
        projects: Project[];
    } = $props();

    const breadcrumbs: BreadcrumbItem[] = $derived([
        { title: 'Organizations', href: dashboard() },
        {
            title: organization.name,
            href: organizations.dashboard.url(organization.public_id),
        },
        {
            title: 'View projects',
            href: organizationProjects.index.url(organization.public_id),
            variant: 'action',
        },
    ]);

    const eventLabels = $derived(
        Object.keys(eventsOverTime).map((value) =>
            new Date(value).toLocaleDateString('en', {
                month: 'short',
                day: 'numeric',
            }),
        ),
    );
    const eventData = $derived(Object.values(eventsOverTime));
    const roleLabels = $derived(Object.keys(roleCounts));
    const roleData = $derived(Object.values(roleCounts));
</script>

{#snippet projectIcon()}
    <FolderKanban class="size-8" />
{/snippet}

{#snippet createProjectAction()}
    <Button size="sm" asChild>
        {#snippet children(props)}
            <Link
                href={organizationProjects.create.url(organization.public_id)}
                class={props.class}
            >
                Create project
            </Link>
        {/snippet}
    </Button>
{/snippet}

<AppHead title={`${organization.name} dashboard`} />

<AppLayout {breadcrumbs}>
    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
        <div
            class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-xl font-semibold">{organization.name}</h2>
                    <Badge variant="secondary">{currentUserRole.label}</Badge>
                </div>
                <p class="mt-1 text-sm text-muted-foreground">
                    Organization activity and project overview
                </p>
            </div>
            <div class="flex gap-2">
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
                {#if canManageProjects}
                    <Button size="sm" asChild>
                        {#snippet children(props)}
                            <Link
                                href={organizationProjects.create.url(
                                    organization.public_id,
                                )}
                                class={props.class}
                            >
                                Create project
                            </Link>
                        {/snippet}
                    </Button>
                {/if}
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <Card>
                <CardHeader class="flex-row items-center justify-between">
                    <CardTitle class="text-sm font-medium">Projects</CardTitle>
                    <FolderKanban class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-3xl font-bold">{stats.projects_count}</div>
                    <p class="text-xs text-muted-foreground">
                        {stats.active_projects_count} active
                    </p>
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="flex-row items-center justify-between">
                    <CardTitle class="text-sm font-medium">Members</CardTitle>
                    <Users class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-3xl font-bold">{stats.members_count}</div>
                    <p class="text-xs text-muted-foreground">
                        Across this organization
                    </p>
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="flex-row items-center justify-between">
                    <CardTitle class="text-sm font-medium">Segments</CardTitle>
                    <Target class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-3xl font-bold">{stats.segments_count}</div>
                    <p class="text-xs text-muted-foreground">
                        {stats.active_segments_count} active
                    </p>
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="flex-row items-center justify-between">
                    <CardTitle class="text-sm font-medium">Events</CardTitle>
                    <Activity class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-3xl font-bold">{stats.events_count}</div>
                    <p class="text-xs text-muted-foreground">
                        {stats.unique_visitors_count} unique visitors
                    </p>
                </CardContent>
            </Card>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <Card class="lg:col-span-2">
                <CardHeader>
                    <CardTitle class="text-sm font-medium">
                        Events - last 30 days
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    {#if eventData.length > 0}
                        <AreaChart
                            labels={eventLabels}
                            data={eventData}
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
            <Card>
                <CardHeader>
                    <CardTitle class="text-sm font-medium">
                        Member roles
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    {#if roleData.length > 0}
                        <DoughnutChart labels={roleLabels} data={roleData} />
                    {:else}
                        <div class="flex h-64 items-center justify-center">
                            <p class="text-sm text-muted-foreground">
                                No members
                            </p>
                        </div>
                    {/if}
                </CardContent>
            </Card>
        </div>

        <div>
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-base font-semibold">Projects</h3>
                <Button variant="ghost" size="sm" asChild>
                    {#snippet children(props)}
                        <Link
                            href={organizationProjects.index.url(
                                organization.public_id,
                            )}
                            class={props.class}
                        >
                            View all
                        </Link>
                    {/snippet}
                </Button>
            </div>
            {#if projects.length === 0}
                <EmptyState
                    icon={projectIcon}
                    title={`No projects in ${organization.name}`}
                    description="Create a project to start collecting events and defining audience segments."
                    actions={canManageProjects
                        ? createProjectAction
                        : undefined}
                />
            {:else}
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    {#each projects as project (project.id)}
                        <Card class="flex flex-col">
                            <CardHeader>
                                <div
                                    class="flex items-start justify-between gap-2"
                                >
                                    <CardTitle class="text-base">
                                        {project.name}
                                    </CardTitle>
                                    <Badge
                                        variant={project.active
                                            ? 'default'
                                            : 'secondary'}
                                    >
                                        {project.active ? 'Active' : 'Inactive'}
                                    </Badge>
                                </div>
                            </CardHeader>
                            <CardContent class="flex-1 text-sm">
                                <span>{project.segments_count} segments</span>
                                <span class="mx-2 text-muted-foreground">·</span
                                >
                                <span>{project.event_logs_count} events</span>
                            </CardContent>
                            <CardFooter>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    class="w-full"
                                    asChild
                                >
                                    {#snippet children(props)}
                                        <Link
                                            href={projectsRoutes.show.url(
                                                project.public_id,
                                            )}
                                            class={props.class}
                                        >
                                            Open project
                                        </Link>
                                    {/snippet}
                                </Button>
                            </CardFooter>
                        </Card>
                    {/each}
                </div>
            {/if}
        </div>
    </div>
</AppLayout>
