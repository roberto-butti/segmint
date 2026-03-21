<script lang="ts">
    import { untrack } from 'svelte';
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
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import segments from '@/routes/projects/segments';

    let {
        projectSlug,
        segmentId,
        segmentName,
        segmentSlug,
        variant = 'outline' as 'outline' | 'default' | 'ghost',
        size = 'sm' as 'sm' | 'default',
        class: className = '',
    }: {
        projectSlug: string;
        segmentId: number;
        segmentName: string;
        segmentSlug: string;
        variant?: 'outline' | 'default' | 'ghost';
        size?: 'sm' | 'default';
        class?: string;
    } = $props();

    let open = $state(false);
    let nameInput = $state('');
    let slugInput = $state('');
    let processing = $state(false);

    function randomSuffix(): string {
        const chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
        let result = '';
        for (let i = 0; i < 5; i++) {
            result += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return result;
    }

    $effect(() => {
        if (open) {
            untrack(() => {
                nameInput = `${segmentName} (copy)`;
                slugInput = `${segmentSlug}-${randomSuffix()}`;
            });
        }
    });

    function handleDuplicate(): void {
        processing = true;
        router.post(
            segments.duplicate.url({ project: projectSlug, segment: segmentId }),
            { name: nameInput, slug: slugInput },
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
    <DialogTrigger>
        <Button {variant} {size} class={className}>
            Duplicate
        </Button>
    </DialogTrigger>
    <DialogContent>
        <DialogTitle>Duplicate segment</DialogTitle>
        <DialogDescription>
            Create a copy of <strong>{segmentName}</strong> with all its rules.
        </DialogDescription>
        <div class="grid gap-4 py-4">
            <div class="grid gap-2">
                <Label for="duplicate-name">Name</Label>
                <Input
                    id="duplicate-name"
                    bind:value={nameInput}
                    placeholder="Segment name"
                    onkeydown={(e) => { if (e.key === 'Enter') handleDuplicate(); }}
                />
            </div>
            <div class="grid gap-2">
                <Label for="duplicate-slug">Slug</Label>
                <Input
                    id="duplicate-slug"
                    bind:value={slugInput}
                    placeholder="segment-slug"
                    class="font-mono"
                    onkeydown={(e) => { if (e.key === 'Enter') handleDuplicate(); }}
                />
                <p class="text-xs text-muted-foreground">Used for matching in the SDK and API.</p>
            </div>
        </div>
        <DialogFooter>
            <DialogClose>
                <Button variant="outline">Cancel</Button>
            </DialogClose>
            <Button onclick={handleDuplicate} disabled={processing || !nameInput.trim() || !slugInput.trim()}>
                {processing ? 'Duplicating...' : 'Duplicate'}
            </Button>
        </DialogFooter>
    </DialogContent>
</Dialog>
