<script lang="ts">
    import type { Snippet } from 'svelte';

    import { cn } from '@/lib/utils';

    let {
        icon,
        title,
        description,
        details,
        actions,
        class: className = '',
    }: {
        icon?: Snippet;
        title: string;
        description: string;
        details?: Snippet;
        actions?: Snippet;
        class?: string;
    } = $props();
</script>

<div
    class={cn(
        'flex min-h-64 flex-col items-center justify-center gap-4 rounded-xl border border-dashed border-sidebar-border p-10 text-center',
        className,
    )}
>
    {#if icon}
        <div class="text-muted-foreground">
            {@render icon()}
        </div>
    {/if}
    <div class="space-y-1">
        <h3 class="font-medium">{title}</h3>
        <p class="max-w-md text-sm text-muted-foreground">{description}</p>
    </div>
    {#if details}
        {@render details()}
    {/if}
    {#if actions}
        <div class="flex flex-wrap items-center justify-center gap-2">
            {@render actions()}
        </div>
    {/if}
</div>
