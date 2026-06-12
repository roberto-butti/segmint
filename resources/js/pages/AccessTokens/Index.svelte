<script lang="ts">
    import KeyRound from 'lucide-svelte/icons/key-round';
    import AppHead from '@/components/AppHead.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import { Button } from '@/components/ui/button';
    import {
        Card,
        CardContent,
        CardFooter,
        CardHeader,
        CardTitle,
    } from '@/components/ui/card';
    import AppLayout from '@/layouts/AppLayout.svelte';
    import { projectBreadcrumbs } from '@/lib/breadcrumbs';
    import type { BreadcrumbOrganization } from '@/lib/breadcrumbs';
    import accessTokens from '@/routes/projects/access-tokens';
    import type { BreadcrumbItem } from '@/types';

    interface Project {
        id: number;
        name: string;
        public_id: string;
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
        organization,
        accessTokens: tokenList,
    }: {
        project: Project;
        organization: BreadcrumbOrganization;
        accessTokens: AccessToken[];
    } = $props();

    const breadcrumbs: BreadcrumbItem[] = $derived([
        ...projectBreadcrumbs(organization, project),
        {
            title: 'Access Tokens',
            href: accessTokens.index.url(project.public_id),
        },
    ]);

    function maskToken(token: string): string {
        return token.slice(0, 8) + '...' + token.slice(-4);
    }
</script>

{#snippet tokenIcon()}
    <KeyRound class="size-8" />
{/snippet}

<AppHead title={`Access Tokens - ${project.name}`} />

<AppLayout {breadcrumbs}>
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
        {#if tokenList.length === 0}
            <EmptyState
                icon={tokenIcon}
                title={`No access tokens for ${project.name}`}
                description="An access token is required to send events to this project. New projects normally receive one automatically."
                class="flex-1"
            />
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
                                    Last used: {new Date(
                                        token.last_used_at,
                                    ).toLocaleDateString()}
                                </p>
                            {:else}
                                <p class="text-xs text-muted-foreground">
                                    Never used
                                </p>
                            {/if}
                        </CardContent>
                        {#if token.active}
                            <CardFooter>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    class="w-full"
                                    asChild
                                >
                                    <a
                                        href={`/playground.html?token=${encodeURIComponent(token.token)}`}
                                        class="inline-flex items-center justify-center gap-1.5"
                                    >
                                        Open playground
                                    </a>
                                </Button>
                            </CardFooter>
                        {/if}
                    </Card>
                {/each}
            </div>
        {/if}
    </div>
</AppLayout>
