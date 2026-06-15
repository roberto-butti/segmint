<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import { untrack } from 'svelte';

    import { Alert, AlertDescription } from '@/components/ui/alert';
    import { Button } from '@/components/ui/button';
    import {
        Dialog,
        DialogClose,
        DialogContent,
        DialogDescription,
        DialogFooter,
        DialogTitle,
        DialogTrigger,
    } from '@/components/ui/dialog';
    import {
        Select,
        SelectContent,
        SelectItem,
        SelectTrigger,
    } from '@/components/ui/select';
    import segments from '@/routes/projects/segments';

    interface Segment {
        id: number;
        name: string;
        slug: string;
    }

    interface DestinationProject {
        id: number;
        name: string;
        public_id: string;
        organization_name: string;
        segment_slugs: string[];
    }

    let {
        sourceProjectPublicId,
        selectedSegments,
        destinationProjects,
    }: {
        sourceProjectPublicId: string;
        selectedSegments: Segment[];
        destinationProjects: DestinationProject[];
    } = $props();

    let open = $state(false);
    let destinationProjectId = $state('');
    let processing = $state(false);

    const destinationProject = $derived(
        destinationProjects.find(
            (project) => project.id.toString() === destinationProjectId,
        ),
    );
    const conflictingSegments = $derived(
        destinationProject
            ? selectedSegments.filter((segment) =>
                  destinationProject.segment_slugs.includes(segment.slug),
              )
            : [],
    );
    const copyableCount = $derived(
        selectedSegments.length - conflictingSegments.length,
    );

    $effect(() => {
        if (open && !destinationProjectId && destinationProjects.length > 0) {
            untrack(() => {
                destinationProjectId = destinationProjects[0].id.toString();
            });
        }
    });

    function handleCopy(): void {
        if (!destinationProject || copyableCount === 0) {
            return;
        }

        processing = true;
        router.post(
            segments.copy.url(sourceProjectPublicId),
            {
                destination_project_id: destinationProject.id,
                segment_ids: selectedSegments.map((segment) => segment.id),
            },
            {
                onSuccess: () => {
                    open = false;
                },
                onFinish: () => {
                    processing = false;
                },
            },
        );
    }
</script>

<Dialog bind:open>
    <DialogTrigger asChild>
        {#snippet children(props)}
            <Button
                variant="outline"
                size="sm"
                onclick={props.onclick}
                aria-expanded={props['aria-expanded']}
                disabled={selectedSegments.length === 0 ||
                    destinationProjects.length === 0}
            >
                Copy to project
                {#if selectedSegments.length > 0}
                    ({selectedSegments.length})
                {/if}
            </Button>
        {/snippet}
    </DialogTrigger>
    <DialogContent>
        <DialogTitle>Copy segments to another project</DialogTitle>
        <DialogDescription>
            Copy the selected segment definitions and rules. Matches and
            analytics are not copied.
        </DialogDescription>

        <div class="grid gap-4 py-4">
            <div class="grid gap-2">
                <span class="text-sm font-medium">Destination project</span>
                <Select bind:value={destinationProjectId}>
                    <SelectTrigger class="w-full">
                        {#if destinationProject}
                            {destinationProject.name}
                            <span class="text-muted-foreground">
                                ({destinationProject.organization_name})
                            </span>
                        {:else}
                            Select a project
                        {/if}
                    </SelectTrigger>
                    <SelectContent>
                        {#each destinationProjects as project (project.id)}
                            <SelectItem value={project.id.toString()}>
                                {project.name} ({project.organization_name})
                            </SelectItem>
                        {/each}
                    </SelectContent>
                </Select>
            </div>

            <div class="rounded-lg border p-3 text-sm">
                <p>
                    <strong>{copyableCount}</strong>
                    {copyableCount === 1 ? 'segment' : 'segments'} will be copied.
                </p>
            </div>

            {#if conflictingSegments.length > 0}
                <Alert>
                    <AlertDescription>
                        <p>
                            {conflictingSegments.length}
                            {conflictingSegments.length === 1
                                ? 'segment has'
                                : 'segments have'}
                            a slug that already exists and will be skipped:
                        </p>
                        <ul class="list-disc pl-5">
                            {#each conflictingSegments as segment (segment.id)}
                                <li>{segment.name} ({segment.slug})</li>
                            {/each}
                        </ul>
                    </AlertDescription>
                </Alert>
            {/if}
        </div>

        <DialogFooter>
            <DialogClose asChild>
                {#snippet children(props)}
                    <Button variant="outline" onclick={props.onclick}>
                        Cancel
                    </Button>
                {/snippet}
            </DialogClose>
            <Button
                onclick={handleCopy}
                disabled={processing ||
                    !destinationProject ||
                    copyableCount === 0}
            >
                {processing
                    ? 'Copying...'
                    : `Copy ${copyableCount} ${copyableCount === 1 ? 'segment' : 'segments'}`}
            </Button>
        </DialogFooter>
    </DialogContent>
</Dialog>
