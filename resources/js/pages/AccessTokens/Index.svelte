<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import {
        Card,
        CardContent,
        CardHeader,
        CardTitle,
    } from '@/components/ui/card';
    import AppLayout from '@/layouts/AppLayout.svelte';
    import projects from '@/routes/projects';
    import accessTokens from '@/routes/projects/access-tokens';
    import type { BreadcrumbItem } from '@/types';

    interface Project {
        id: number;
        name: string;
        slug: string;
    }

    interface AccessToken {
        id: number;
        name: string | null;
        token: string;
        active: boolean;
        last_used_at: string | null;
        created_at: string;
    }

    let {
        project,
        accessTokens: tokenList,
    }: {
        project: Project;
        accessTokens: AccessToken[];
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
            title: 'Access Tokens',
            href: accessTokens.index.url(project.slug),
        },
    ];

    function maskToken(token: string): string {
        return token.slice(0, 8) + '...' + token.slice(-4);
    }
</script>

<AppHead title={`Access Tokens - ${project.name}`} />

<AppLayout {breadcrumbs}>
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
        {#if tokenList.length === 0}
            <div
                class="flex flex-1 items-center justify-center rounded-xl border border-dashed border-sidebar-border p-12"
            >
                <p class="text-muted-foreground">
                    No access tokens yet.
                </p>
            </div>
        {:else}
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                {#each tokenList as token (token.id)}
                    <Card>
                        <CardHeader>
                            <div class="flex items-center justify-between">
                                <CardTitle class="text-base">
                                    {token.name ?? 'Unnamed token'}
                                </CardTitle>
                                <span
                                    class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {token.active
                                        ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300'
                                        : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'}"
                                >
                                    {token.active ? 'Active' : 'Revoked'}
                                </span>
                            </div>
                        </CardHeader>
                        <CardContent class="space-y-1">
                            <p class="font-mono text-sm text-muted-foreground">
                                {maskToken(token.token)}
                            </p>
                            {#if token.last_used_at}
                                <p class="text-xs text-muted-foreground">
                                    Last used: {new Date(token.last_used_at).toLocaleDateString()}
                                </p>
                            {:else}
                                <p class="text-xs text-muted-foreground">
                                    Never used
                                </p>
                            {/if}
                        </CardContent>
                    </Card>
                {/each}
            </div>
        {/if}
    </div>
</AppLayout>
