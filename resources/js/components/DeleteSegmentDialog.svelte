<script lang="ts">
    import { router } from '@inertiajs/svelte';
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
    import segments from '@/routes/projects/segments';

    let {
        projectSlug,
        segmentId,
        segmentName,
        variant = 'outline' as 'outline' | 'default' | 'ghost',
        size = 'sm' as 'sm' | 'default',
        class: className = '',
    }: {
        projectSlug: string;
        segmentId: number;
        segmentName: string;
        variant?: 'outline' | 'default' | 'ghost';
        size?: 'sm' | 'default';
        class?: string;
    } = $props();

    let open = $state(false);
    let processing = $state(false);

    function handleDelete(): void {
        processing = true;
        router.delete(
            segments.destroy.url({ project: projectSlug, segment: segmentId }),
            {
                onFinish: () => {
                    processing = false;
                    open = false;
                },
            },
        );
    }
</script>

<Dialog bind:open>
    <DialogTrigger asChild>
        {#snippet children(props)}
            <Button
                {variant}
                {size}
                class="text-destructive hover:text-destructive {className}"
                onclick={props.onclick}
                aria-expanded={props['aria-expanded']}
            >
                Delete
            </Button>
        {/snippet}
    </DialogTrigger>
    <DialogContent>
        <DialogTitle>Delete segment</DialogTitle>
        <DialogDescription>
            Are you sure you want to delete <strong>{segmentName}</strong>? This
            will permanently remove the segment and all its rules. This action
            cannot be undone.
        </DialogDescription>
        <DialogFooter>
            <DialogClose asChild>
                {#snippet children(props)}
                    <Button variant="outline" onclick={props.onclick}
                        >Cancel</Button
                    >
                {/snippet}
            </DialogClose>
            <Button
                variant="destructive"
                onclick={handleDelete}
                disabled={processing}
            >
                {processing ? 'Deleting...' : 'Delete segment'}
            </Button>
        </DialogFooter>
    </DialogContent>
</Dialog>
