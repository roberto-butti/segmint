<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import DeleteSegmentDialog from '@/components/DeleteSegmentDialog.svelte';
    import DuplicateSegmentDialog from '@/components/DuplicateSegmentDialog.svelte';
    import Heading from '@/components/Heading.svelte';
    import { Button } from '@/components/ui/button';
    import {
        Card,
        CardContent,
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

    interface SegmentRule {
        id: number;
        type: string;
        key: string;
        operator: string;
        value: string;
        priority: number;
    }

    interface EnumOption {
        value: string;
        label: string;
    }

    interface Segment {
        id: number;
        name: string;
        slug: string;
        description: string | null;
        active: boolean;
        rules: SegmentRule[];
        created_at: string;
        updated_at: string;
    }

    let {
        project,
        segment,
        ruleTypes,
        ruleOperators,
    }: {
        project: Project;
        segment: Segment;
        ruleTypes: EnumOption[];
        ruleOperators: EnumOption[];
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
        {
            title: segment.name,
            href: segments.show.url({
                project: project.slug,
                segment: segment.id,
            }),
        },
    ];

    function getTypeLabel(value: string): string {
        return ruleTypes.find((t) => t.value === value)?.label ?? value;
    }

    function getOperatorLabel(value: string): string {
        return ruleOperators.find((o) => o.value === value)?.label ?? value;
    }

    let copiedSnippet = $state<string | null>(null);

    function copyToClipboard(text: string, label: string): void {
        navigator.clipboard.writeText(text);
        copiedSnippet = label;
        setTimeout(() => {
            copiedSnippet = null;
        }, 2000);
    }

    const snippetCheck = `if (Segmint.visitor.hasSegment('${segment.slug}')) {
  // Show personalised content for "${segment.name}"
}`;

    const snippetInit = `Segmint.init({ token: 'your-project-token', autoTrack: true })
  .then(function () {
    if (Segmint.visitor.hasSegment('${segment.slug}')) {
      document.getElementById('banner').style.display = 'block';
    }
  });`;

    const snippetDataAttr = `<!-- Tag your content block with this segment -->
<div data-segment="${segment.slug}" style="display: none;">
  <!-- Content shown only to "${segment.name}" visitors -->
</div>`;

    const snippetOnReady = `Segmint.onReady(function (segments) {
  if (Segmint.visitor.hasSegment('${segment.slug}')) {
    console.log('Visitor matches: ${segment.name}');
  }
});`;
</script>

<AppHead title={segment.name} />

<AppLayout {breadcrumbs}>
    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
        <div class="flex items-center justify-between">
            <Heading
                variant="small"
                title={segment.name}
                description={segment.description ?? ''}
            />
            <div class="flex items-center gap-3">
                <span
                    class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium {segment.active
                        ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300'
                        : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'}"
                >
                    {segment.active ? 'Active' : 'Inactive'}
                </span>
                <DuplicateSegmentDialog
                    projectSlug={project.slug}
                    segmentId={segment.id}
                    segmentName={segment.name}
                    segmentSlug={segment.slug}
                />
                <Button variant="outline" size="sm">
                    <Link
                        href={segments.edit.url({
                            project: project.slug,
                            segment: segment.id,
                        })}>Edit</Link
                    >
                </Button>
                <DeleteSegmentDialog
                    projectSlug={project.slug}
                    segmentId={segment.id}
                    segmentName={segment.name}
                />
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-sm font-medium"
                            >Details</CardTitle
                        >
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div>
                            <p
                                class="text-xs font-medium text-muted-foreground"
                            >
                                Slug
                            </p>
                            <p class="font-mono text-sm">{segment.slug}</p>
                        </div>
                        <div>
                            <p
                                class="text-xs font-medium text-muted-foreground"
                            >
                                Status
                            </p>
                            <p class="text-sm">
                                {segment.active ? 'Active' : 'Inactive'}
                            </p>
                        </div>
                        {#if segment.description}
                            <div>
                                <p
                                    class="text-xs font-medium text-muted-foreground"
                                >
                                    Description
                                </p>
                                <p class="text-sm">{segment.description}</p>
                            </div>
                        {/if}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-sm font-medium">
                            Rules ({segment.rules.length})
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {#if segment.rules.length === 0}
                            <p class="text-sm text-muted-foreground">
                                No rules defined.
                            </p>
                        {:else}
                            <div class="space-y-3">
                                {#each segment.rules as rule, index (rule.id)}
                                    <div class="rounded-lg border p-3">
                                        <div
                                            class="flex items-center justify-between"
                                        >
                                            <span
                                                class="text-xs font-medium text-muted-foreground"
                                                >Rule {index + 1}</span
                                            >
                                            <span
                                                class="rounded bg-muted px-1.5 py-0.5 text-xs"
                                                >{getTypeLabel(rule.type)}</span
                                            >
                                        </div>
                                        <p class="mt-1 text-sm">
                                            <span class="font-mono"
                                                >{rule.key}</span
                                            >
                                            <span
                                                class="mx-1 text-muted-foreground"
                                                >{getOperatorLabel(
                                                    rule.operator,
                                                )}</span
                                            >
                                            <span class="font-mono"
                                                >{rule.value}</span
                                            >
                                        </p>
                                    </div>
                                {/each}
                            </div>
                        {/if}
                    </CardContent>
                </Card>
            </div>

            <div class="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-sm font-medium"
                            >SDK snippets</CardTitle
                        >
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <p class="text-sm text-muted-foreground">
                            Copy these snippets to use the <span
                                class="font-mono">{segment.slug}</span
                            > segment in your frontend.
                        </p>

                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <p class="text-xs font-medium">
                                    Check segment membership
                                </p>
                                <button
                                    class="text-xs text-muted-foreground hover:text-foreground"
                                    onclick={() =>
                                        copyToClipboard(snippetCheck, 'check')}
                                >
                                    {copiedSnippet === 'check'
                                        ? 'Copied!'
                                        : 'Copy'}
                                </button>
                            </div>
                            <pre
                                class="overflow-x-auto rounded-lg bg-muted p-3 text-xs"><code
                                    >{snippetCheck}</code
                                ></pre>
                        </div>

                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <p class="text-xs font-medium">
                                    Personalise on page load
                                </p>
                                <button
                                    class="text-xs text-muted-foreground hover:text-foreground"
                                    onclick={() =>
                                        copyToClipboard(snippetInit, 'init')}
                                >
                                    {copiedSnippet === 'init'
                                        ? 'Copied!'
                                        : 'Copy'}
                                </button>
                            </div>
                            <pre
                                class="overflow-x-auto rounded-lg bg-muted p-3 text-xs"><code
                                    >{snippetInit}</code
                                ></pre>
                        </div>

                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <p class="text-xs font-medium">
                                    HTML data attribute
                                </p>
                                <button
                                    class="text-xs text-muted-foreground hover:text-foreground"
                                    onclick={() =>
                                        copyToClipboard(
                                            snippetDataAttr,
                                            'data',
                                        )}
                                >
                                    {copiedSnippet === 'data'
                                        ? 'Copied!'
                                        : 'Copy'}
                                </button>
                            </div>
                            <pre
                                class="overflow-x-auto rounded-lg bg-muted p-3 text-xs"><code
                                    >{snippetDataAttr}</code
                                ></pre>
                        </div>

                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <p class="text-xs font-medium">
                                    onReady callback
                                </p>
                                <button
                                    class="text-xs text-muted-foreground hover:text-foreground"
                                    onclick={() =>
                                        copyToClipboard(
                                            snippetOnReady,
                                            'ready',
                                        )}
                                >
                                    {copiedSnippet === 'ready'
                                        ? 'Copied!'
                                        : 'Copy'}
                                </button>
                            </div>
                            <pre
                                class="overflow-x-auto rounded-lg bg-muted p-3 text-xs"><code
                                    >{snippetOnReady}</code
                                ></pre>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</AppLayout>
