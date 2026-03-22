<script lang="ts">
    import { Link, router } from '@inertiajs/svelte';
    import Check from 'lucide-svelte/icons/check';
    import GitCompare from 'lucide-svelte/icons/git-compare';
    import Lightbulb from 'lucide-svelte/icons/lightbulb';
    import Sparkles from 'lucide-svelte/icons/sparkles';
    import AppHead from '@/components/AppHead.svelte';
    import { Button } from '@/components/ui/button';
    import {
        Card,
        CardContent,
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

    interface SuggestionRule {
        type: string;
        key: string;
        operator: string;
        value: string;
    }

    interface EnumOption {
        value: string;
        label: string;
    }

    interface ExistingSegment {
        id: number;
        name: string;
        slug: string;
        matchingRule?: {
            type: string;
            key: string;
            operator: string;
            value: string;
        };
    }

    interface Suggestion {
        name: string;
        slug: string;
        description: string;
        rules: SuggestionRule[];
        confidence: string;
        reason: string;
        category: string;
        status: 'new' | 'similar' | 'exists';
        existingSegment: ExistingSegment | null;
    }

    let {
        project,
        suggestions,
        ruleTypes,
        ruleOperators,
    }: {
        project: Project;
        suggestions: Suggestion[];
        ruleTypes: EnumOption[];
        ruleOperators: EnumOption[];
    } = $props();

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Projects', href: projects.index.url() },
        { title: project.name, href: projects.show.url(project.slug) },
        { title: 'Segments', href: segments.index.url(project.slug) },
        { title: 'Suggestions', href: segments.suggestions.url(project.slug) },
    ];

    function getTypeLabel(value: string): string {
        return ruleTypes.find((t) => t.value === value)?.label ?? value;
    }

    function getOperatorLabel(value: string): string {
        return ruleOperators.find((o) => o.value === value)?.label ?? value;
    }

    function confidenceColor(confidence: string): string {
        return confidence === 'high'
            ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300'
            : confidence === 'medium'
              ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300'
              : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400';
    }

    let creating = $state<string | null>(null);

    function createSegment(suggestion: Suggestion): void {
        creating = suggestion.slug;
        router.post(
            segments.store.url(project.slug),
            {
                name: suggestion.name,
                description: suggestion.description,
                active: true,
                rules: suggestion.rules.map((r, i) => ({
                    ...r,
                    priority: i,
                })),
            },
            {
                preserveState: false,
                onError: () => {
                    creating = null;
                },
            },
        );
    }

    const categories = $derived(
        [...new Set(suggestions.map((s) => s.category))].sort(),
    );
</script>

<AppHead title={`Segment Suggestions - ${project.name}`} />

<AppLayout {breadcrumbs}>
    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <Sparkles class="size-5 text-amber-500" />
                <h2 class="text-xl font-semibold">Segment Suggestions</h2>
            </div>
            <p class="text-sm text-muted-foreground">
                {suggestions.length}
                {suggestions.length === 1 ? 'suggestion' : 'suggestions'} based on
                your event data
            </p>
        </div>

        {#if suggestions.length === 0}
            <div
                class="flex flex-1 flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-sidebar-border p-12"
            >
                <Lightbulb class="size-8 text-muted-foreground" />
                <p class="text-muted-foreground">
                    No suggestions yet. Track more events to get data-driven
                    segment recommendations.
                </p>
                <Button
                    variant="outline"
                    size="sm"
                    onclick={() => router.get(segments.index.url(project.slug))}
                >
                    Back to segments
                </Button>
            </div>
        {:else}
            {#each categories as category (category)}
                <div class="space-y-3">
                    <h3 class="text-sm font-medium text-muted-foreground">
                        {category}
                    </h3>
                    <div
                        class="grid auto-rows-fr gap-4 md:grid-cols-2 lg:grid-cols-3"
                    >
                        {#each suggestions.filter((s) => s.category === category) as suggestion (suggestion.slug)}
                            <Card
                                class="flex flex-col {suggestion.status ===
                                'exists'
                                    ? 'opacity-60'
                                    : ''}"
                            >
                                <CardHeader>
                                    <div
                                        class="flex items-center justify-between gap-2"
                                    >
                                        <CardTitle class="truncate text-base"
                                            >{suggestion.name}</CardTitle
                                        >
                                        <div
                                            class="flex shrink-0 items-center gap-1.5"
                                        >
                                            {#if suggestion.status === 'exists'}
                                                <span
                                                    class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900 dark:text-blue-300"
                                                >
                                                    <Check class="size-3" />
                                                    Exists
                                                </span>
                                            {:else if suggestion.status === 'similar'}
                                                <span
                                                    class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-900 dark:text-amber-300"
                                                >
                                                    <GitCompare
                                                        class="size-3"
                                                    />
                                                    Similar
                                                </span>
                                            {:else}
                                                <span
                                                    class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {confidenceColor(
                                                        suggestion.confidence,
                                                    )}"
                                                >
                                                    {suggestion.confidence}
                                                </span>
                                            {/if}
                                        </div>
                                    </div>
                                </CardHeader>
                                <CardContent class="flex-1 space-y-3">
                                    <p class="text-sm text-muted-foreground">
                                        {suggestion.description}
                                    </p>

                                    <div class="space-y-1.5">
                                        {#each suggestion.rules as rule, index (index)}
                                            <div
                                                class="rounded border bg-muted/50 px-3 py-2 text-xs"
                                            >
                                                <span
                                                    class="rounded bg-muted px-1.5 py-0.5 font-medium"
                                                    >{getTypeLabel(
                                                        rule.type,
                                                    )}</span
                                                >
                                                {#if rule.key}
                                                    <span class="mx-1 font-mono"
                                                        >{rule.key}</span
                                                    >
                                                {/if}
                                                <span
                                                    class="text-muted-foreground"
                                                    >{getOperatorLabel(
                                                        rule.operator,
                                                    )}</span
                                                >
                                                <span class="font-mono">
                                                    {rule.value}</span
                                                >
                                            </div>
                                        {/each}
                                    </div>

                                    {#if suggestion.status === 'exists' && suggestion.existingSegment}
                                        <div
                                            class="rounded border border-blue-200 bg-blue-50 px-3 py-2 text-xs dark:border-blue-800 dark:bg-blue-900/30"
                                        >
                                            <span
                                                class="text-blue-700 dark:text-blue-300"
                                            >
                                                Already exists as
                                                <Link
                                                    href={segments.show.url({
                                                        project: project.slug,
                                                        segment:
                                                            suggestion
                                                                .existingSegment
                                                                .id,
                                                    })}
                                                    class="font-medium underline"
                                                >
                                                    {suggestion.existingSegment
                                                        .name}
                                                </Link>
                                            </span>
                                        </div>
                                    {:else if suggestion.status === 'similar' && suggestion.existingSegment}
                                        <div
                                            class="rounded border border-amber-200 bg-amber-50 px-3 py-2 text-xs dark:border-amber-800 dark:bg-amber-900/30"
                                        >
                                            <span
                                                class="text-amber-700 dark:text-amber-300"
                                            >
                                                Similar to
                                                <Link
                                                    href={segments.show.url({
                                                        project: project.slug,
                                                        segment:
                                                            suggestion
                                                                .existingSegment
                                                                .id,
                                                    })}
                                                    class="font-medium underline"
                                                >
                                                    {suggestion.existingSegment
                                                        .name}
                                                </Link>
                                                {#if suggestion.existingSegment.matchingRule}
                                                    <span class="block mt-1">
                                                        Existing value: <span
                                                            class="font-mono"
                                                            >{suggestion
                                                                .existingSegment
                                                                .matchingRule
                                                                .value}</span
                                                        >
                                                    </span>
                                                {/if}
                                            </span>
                                        </div>
                                    {:else}
                                        <p
                                            class="text-xs text-muted-foreground"
                                        >
                                            <Lightbulb
                                                class="mr-1 inline size-3"
                                            />
                                            {suggestion.reason}
                                        </p>
                                    {/if}
                                </CardContent>
                                <CardFooter>
                                    {#if suggestion.status === 'exists'}
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            class="w-full"
                                            asChild
                                        >
                                            <Link
                                                href={segments.show.url({
                                                    project: project.slug,
                                                    segment:
                                                        suggestion
                                                            .existingSegment
                                                            ?.id ?? 0,
                                                })}
                                            >
                                                View existing segment
                                            </Link>
                                        </Button>
                                    {:else}
                                        <Button
                                            variant="default"
                                            size="sm"
                                            class="w-full"
                                            onclick={() =>
                                                createSegment(suggestion)}
                                            disabled={creating ===
                                                suggestion.slug}
                                        >
                                            {creating === suggestion.slug
                                                ? 'Creating...'
                                                : 'Create this segment'}
                                        </Button>
                                    {/if}
                                </CardFooter>
                            </Card>
                        {/each}
                    </div>
                </div>
            {/each}
        {/if}
    </div>
</AppLayout>
