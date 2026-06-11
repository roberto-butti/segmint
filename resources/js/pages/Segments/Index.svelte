<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import X from 'lucide-svelte/icons/x';
    import AppHead from '@/components/AppHead.svelte';
    import CopySegmentsDialog from '@/components/CopySegmentsDialog.svelte';
    import DeleteSegmentDialog from '@/components/DeleteSegmentDialog.svelte';
    import DuplicateSegmentDialog from '@/components/DuplicateSegmentDialog.svelte';
    import { Alert, AlertDescription } from '@/components/ui/alert';
    import { Button } from '@/components/ui/button';
    import {
        Card,
        CardContent,
        CardDescription,
        CardFooter,
        CardHeader,
        CardTitle,
    } from '@/components/ui/card';
    import { Checkbox } from '@/components/ui/checkbox';
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

    interface DestinationProject {
        id: number;
        name: string;
        slug: string;
        organization_name: string;
        segment_slugs: string[];
    }

    let {
        project,
        segments: segmentList,
        destinationProjects,
    }: {
        project: Project;
        segments: Segment[];
        destinationProjects: DestinationProject[];
    } = $props();

    let selected = $state<Record<number, boolean>>({});

    const selectedSegments = $derived(
        segmentList.filter((segment) => selected[segment.id]),
    );
    const allSelected = $derived(
        segmentList.length > 0 &&
            selectedSegments.length === segmentList.length,
    );
    const successMessage = $derived(page.props.flash.success);
    const segmentCopy = $derived(page.props.flash.segmentCopy);
    let dismissedCopyMessageId = $state<string | null>(null);

    function selectAll(): void {
        selected = Object.fromEntries(
            segmentList.map((segment) => [segment.id, true]),
        );
    }

    function clearSelection(): void {
        selected = {};
    }

    const breadcrumbs: BreadcrumbItem[] = $derived([
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
    ]);
</script>

<AppHead title={`Segments - ${project.name}`} />

<AppLayout {breadcrumbs}>
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold">Segments</h2>
            <div class="flex items-center gap-2">
                <CopySegmentsDialog
                    sourceProjectSlug={project.slug}
                    {selectedSegments}
                    {destinationProjects}
                />
                <Button variant="outline" size="sm">
                    <Link href={segments.suggestions.url(project.slug)}>
                        Suggestions
                    </Link>
                </Button>
                <Button variant="default" size="sm">
                    <Link href={segments.create.url(project.slug)}>
                        Create segment
                    </Link>
                </Button>
            </div>
        </div>

        {#if segmentCopy && dismissedCopyMessageId !== segmentCopy.id}
            <Alert class="pr-12">
                <AlertDescription>
                    <p>
                        {segmentCopy.message}
                        <Link
                            href={segmentCopy.destination_url}
                            class="font-medium text-foreground underline underline-offset-4"
                        >
                            View {segmentCopy.destination_name} segments
                        </Link>
                    </p>
                </AlertDescription>
                <button
                    type="button"
                    class="absolute top-3 right-3 rounded-sm p-1 text-muted-foreground hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    aria-label="Dismiss copy result"
                    onclick={() => {
                        dismissedCopyMessageId = segmentCopy.id;
                    }}
                >
                    <X class="size-4" />
                </button>
            </Alert>
        {:else if successMessage}
            <Alert>
                <AlertDescription>{successMessage}</AlertDescription>
            </Alert>
        {/if}

        {#if segmentList.length === 0}
            <div
                class="flex flex-1 items-center justify-center rounded-xl border border-dashed border-sidebar-border p-12"
            >
                <p class="text-muted-foreground">
                    No segments yet. Create your first segment to get started.
                </p>
            </div>
        {:else}
            <div class="flex items-center gap-2">
                <Button
                    variant="ghost"
                    size="sm"
                    onclick={allSelected ? clearSelection : selectAll}
                >
                    {allSelected ? 'Clear selection' : 'Select all'}
                </Button>
                {#if selectedSegments.length > 0}
                    <span class="text-sm text-muted-foreground">
                        {selectedSegments.length}
                        {selectedSegments.length === 1
                            ? 'segment selected'
                            : 'segments selected'}
                    </span>
                {/if}
                {#if destinationProjects.length === 0}
                    <span class="text-sm text-muted-foreground">
                        No other manageable projects available.
                    </span>
                {/if}
            </div>
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                {#each segmentList as segment (segment.id)}
                    <Card>
                        <CardHeader>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <Checkbox
                                        id={`segment-${segment.id}`}
                                        aria-label={`Select ${segment.name}`}
                                        checked={selected[segment.id] ?? false}
                                        onclick={() => {
                                            selected[segment.id] = !(
                                                selected[segment.id] ?? false
                                            );
                                        }}
                                    />
                                    <CardTitle class="text-base">
                                        {segment.name}
                                    </CardTitle>
                                </div>
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
