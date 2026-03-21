<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import DeleteSegmentDialog from '@/components/DeleteSegmentDialog.svelte';
    import DuplicateSegmentDialog from '@/components/DuplicateSegmentDialog.svelte';
    import { Button } from '@/components/ui/button';
    import {
        Card,
        CardContent,
        CardDescription,
        CardFooter,
        CardHeader,
        CardTitle,
    } from '@/components/ui/card';
    import AppLayout from '@/layouts/AppLayout.svelte';
    import projects from '@/routes/projects';
    import segments from '@/routes/projects/segments';
    import type { BreadcrumbItem } from '@/types';

    interface Project {
        id: number;
        name: string;
        slug: string;
    }

    interface Segment {
        id: number;
        name: string;
        slug: string;
        description: string | null;
        active: boolean;
        value: string;
        rules_count: number;
        created_at: string;
    }

    let {
        project,
        segments: segmentList,
    }: {
        project: Project;
        segments: Segment[];
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
        {
            title: 'Segments',
            href: segments.index.url(project.slug),
        },
    ];
</script>

<AppHead title={`Segments - ${project.name}`} />

<AppLayout {breadcrumbs}>
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold">Segments</h2>
            <Button variant="default" size="sm">
                <Link href={segments.create.url(project.slug)}
                    >Create segment</Link
                >
            </Button>
        </div>

        {#if segmentList.length === 0}
            <div
                class="flex flex-1 items-center justify-center rounded-xl border border-dashed border-sidebar-border p-12"
            >
                <p class="text-muted-foreground">
                    No segments yet. Create your first segment to get started.
                </p>
            </div>
        {:else}
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                {#each segmentList as segment (segment.id)}
                    <Card>
                        <CardHeader>
                            <div class="flex items-center justify-between">
                                <CardTitle class="text-base">
                                    {segment.name}
                                </CardTitle>
                                <span
                                    class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {segment.active
                                        ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300'
                                        : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'}"
                                >
                                    {segment.active ? 'Active' : 'Inactive'}
                                </span>
                            </div>
                            {#if segment.description}
                                <CardDescription>
                                    {segment.description}
                                </CardDescription>
                            {/if}
                        </CardHeader>
                        <CardContent>
                            <p class="text-xs text-muted-foreground">
                                {segment.slug}
                            </p>
                            <p class="mt-1 text-xs text-muted-foreground">
                                {segment.rules_count}
                                {segment.rules_count === 1 ? 'rule' : 'rules'}
                            </p>
                        </CardContent>
                        <CardFooter class="gap-2">
                            <Button variant="outline" size="sm" class="flex-1">
                                <Link
                                    href={segments.show.url({
                                        project: project.slug,
                                        segment: segment.id,
                                    })}>View</Link
                                >
                            </Button>
                            <Button variant="outline" size="sm" class="flex-1">
                                <Link
                                    href={segments.edit.url({
                                        project: project.slug,
                                        segment: segment.id,
                                    })}>Edit</Link
                                >
                            </Button>
                            <DuplicateSegmentDialog
                                projectSlug={project.slug}
                                segmentId={segment.id}
                                segmentName={segment.name}
                                segmentSlug={segment.slug}
                                class="flex-1"
                            />
                            <DeleteSegmentDialog
                                projectSlug={project.slug}
                                segmentId={segment.id}
                                segmentName={segment.name}
                                class="flex-1"
                            />
                        </CardFooter>
                    </Card>
                {/each}
            </div>
        {/if}
    </div>
</AppLayout>
