<script lang="ts">
    import { Link, router } from '@inertiajs/svelte';
    import FolderKanban from 'lucide-svelte/icons/folder-kanban';
    import Star from 'lucide-svelte/icons/star';
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
    import favoriteProjects from '@/routes/projects/favorite';
    import type { BreadcrumbItem } from '@/types';

    interface Project {
        id: number;
        name: string;
        public_id: string;
        description: string | null;
        active: boolean;
        created_at: string;
        is_favorite: boolean;
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

    const favoriteProjectList = $derived(
        projectList.filter((project) => project.is_favorite),
    );
    const otherProjectList = $derived(
        projectList.filter((project) => !project.is_favorite),
    );

    function toggleFavorite(project: Project): void {
        const url = project.is_favorite
            ? favoriteProjects.destroy.url(project.public_id)
            : favoriteProjects.store.url(project.public_id);

        if (project.is_favorite) {
            router.delete(url, { preserveScroll: true });
        } else {
            router.post(url, {}, { preserveScroll: true });
        }
    }
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

{#snippet projectGrid(projectsToDisplay: Project[])}
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        {#each projectsToDisplay as project (project.id)}
            <Card class="transition-colors hover:border-foreground/20">
                <CardHeader>
                    <div class="flex items-start gap-3">
                        <Link
                            href={projects.show.url(project.public_id)}
                            class="min-w-0 flex-1 rounded-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        >
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <CardTitle class="truncate text-base">
                                    {project.name}
                                </CardTitle>
                                <span
                                    class="inline-flex shrink-0 items-center rounded-full px-2 py-0.5 text-xs font-medium {project.active
                                        ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300'
                                        : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'}"
                                >
                                    {project.active ? 'Active' : 'Inactive'}
                                </span>
                            </div>
                            {#if project.description}
                                <CardDescription class="mt-1.5">
                                    {project.description}
                                </CardDescription>
                            {/if}
                        </Link>
                        <Button
                            variant="ghost"
                            size="icon"
                            class="size-8 shrink-0"
                            aria-label={project.is_favorite
                                ? `Remove ${project.name} from favorites`
                                : `Add ${project.name} to favorites`}
                            aria-pressed={project.is_favorite}
                            title={project.is_favorite
                                ? 'Remove from favorites'
                                : 'Add to favorites'}
                            onclick={() => toggleFavorite(project)}
                        >
                            <Star
                                class="size-4 {project.is_favorite
                                    ? 'fill-amber-400 text-amber-500'
                                    : 'text-muted-foreground'}"
                            />
                        </Button>
                    </div>
                </CardHeader>
            </Card>
        {/each}
    </div>
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
            {#if favoriteProjectList.length > 0}
                <section class="space-y-3" aria-labelledby="favorite-projects">
                    <div>
                        <h3 id="favorite-projects" class="font-semibold">
                            Favorite projects
                        </h3>
                        <p class="text-sm text-muted-foreground">
                            Projects you marked for quick access
                        </p>
                    </div>
                    {@render projectGrid(favoriteProjectList)}
                </section>
            {/if}

            {#if otherProjectList.length > 0}
                <section class="space-y-3" aria-labelledby="other-projects">
                    <div>
                        <h3 id="other-projects" class="font-semibold">
                            {favoriteProjectList.length > 0
                                ? 'Other projects'
                                : 'Projects'}
                        </h3>
                        {#if favoriteProjectList.length > 0}
                            <p class="text-sm text-muted-foreground">
                                Projects not currently marked as favorites
                            </p>
                        {/if}
                    </div>
                    {@render projectGrid(otherProjectList)}
                </section>
            {/if}
        {/if}
    </div>
</AppLayout>
