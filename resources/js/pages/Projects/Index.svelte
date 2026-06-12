<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import FolderKanban from 'lucide-svelte/icons/folder-kanban';
    import AppHead from '@/components/AppHead.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import { Button } from '@/components/ui/button';
    import {
        Card,
        CardDescription,
        CardHeader,
        CardTitle,
    } from '@/components/ui/card';
    import AppLayout from '@/layouts/AppLayout.svelte';
    import { organizationBreadcrumbs } from '@/lib/breadcrumbs';
    import type { BreadcrumbOrganization } from '@/lib/breadcrumbs';
    import organizationsRoutes from '@/routes/organizations';
    import organizationProjects from '@/routes/organizations/projects';
    import projects from '@/routes/projects';
    import type { BreadcrumbItem } from '@/types';

    interface Project {
        id: number;
        name: string;
        public_id: string;
        description: string | null;
        active: boolean;
        created_at: string;
    }

    let {
        organization,
        canManageProjects,
        projects: projectList,
    }: {
        organization: BreadcrumbOrganization;
        canManageProjects: boolean;
        projects: Project[];
    } = $props();

    const breadcrumbs: BreadcrumbItem[] = $derived([
        ...organizationBreadcrumbs(organization),
        {
            title: 'Projects',
            href: organizationProjects.index.url(organization.public_id),
        },
    ]);
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

<AppHead title={`${organization.name} projects`} />

<AppLayout {breadcrumbs}>
    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold">
                    {organization.name} projects
                </h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    Projects belonging to this organization
                </p>
            </div>
            <div class="flex items-center gap-2">
                <Button variant="outline" size="sm" asChild>
                    {#snippet children(props)}
                        <Link
                            href={organizationsRoutes.dashboard.url(
                                organization.public_id,
                            )}
                            class={props.class}
                        >
                            Organization dashboard
                        </Link>
                    {/snippet}
                </Button>
                {#if canManageProjects}
                    <Button variant="default" size="sm" asChild>
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

        {#if projectList.length === 0}
            <EmptyState
                icon={projectIcon}
                title={`No projects in ${organization.name}`}
                description="Projects keep event data, segments, and access tokens separated within this organization."
                class="flex-1"
                actions={canManageProjects ? createProjectAction : undefined}
            />
        {:else}
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                {#each projectList as project (project.id)}
                    <Link
                        href={projects.show.url(project.public_id)}
                        class="block"
                    >
                        <Card
                            class="transition-colors hover:border-foreground/20"
                        >
                            <CardHeader>
                                <div class="flex items-center justify-between">
                                    <CardTitle class="text-base">
                                        {project.name}
                                    </CardTitle>
                                    <span
                                        class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {project.active
                                            ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300'
                                            : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'}"
                                    >
                                        {project.active ? 'Active' : 'Inactive'}
                                    </span>
                                </div>
                                {#if project.description}
                                    <CardDescription>
                                        {project.description}
                                    </CardDescription>
                                {/if}
                            </CardHeader>
                        </Card>
                    </Link>
                {/each}
            </div>
        {/if}
    </div>
</AppLayout>
