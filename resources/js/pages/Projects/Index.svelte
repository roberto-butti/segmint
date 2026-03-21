<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import {
        Card,
        CardContent,
        CardDescription,
        CardHeader,
        CardTitle,
    } from '@/components/ui/card';
    import AppLayout from '@/layouts/AppLayout.svelte';
    import projects from '@/routes/projects';
    import type { BreadcrumbItem } from '@/types';

    interface Project {
        id: number;
        name: string;
        slug: string;
        description: string | null;
        active: boolean;
        created_at: string;
    }

    let { projects: projectList }: { projects: Project[] } = $props();

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Projects',
            href: projects.index.url(),
        },
    ];
</script>

<AppHead title="Projects" />

<AppLayout {breadcrumbs}>
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
        {#if projectList.length === 0}
            <div
                class="flex flex-1 items-center justify-center rounded-xl border border-dashed border-sidebar-border p-12"
            >
                <p class="text-muted-foreground">
                    No projects yet.
                </p>
            </div>
        {:else}
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                {#each projectList as project (project.id)}
                    <Link href={projects.show.url(project.slug)} class="block">
                    <Card class="transition-colors hover:border-foreground/20">
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
                        <CardContent>
                            <p class="text-xs text-muted-foreground">
                                {project.slug}
                            </p>
                        </CardContent>
                    </Card>
                    </Link>
                {/each}
            </div>
        {/if}
    </div>
</AppLayout>
