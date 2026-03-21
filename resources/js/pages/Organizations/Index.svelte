<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import Check from 'lucide-svelte/icons/check';
    import Crown from 'lucide-svelte/icons/crown';
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
    import organizations from '@/routes/organizations';
    import type { BreadcrumbItem } from '@/types';

    interface OrganizationItem {
        id: number;
        name: string;
        slug: string;
        role: string;
        projects_count: number;
    }

    let {
        organizations: orgList,
        currentOrganizationId,
    }: {
        organizations: OrganizationItem[];
        currentOrganizationId: number | null;
    } = $props();

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Organizations',
            href: organizations.index.url(),
        },
    ];

    let switching = $state<number | null>(null);

    function switchOrg(orgId: number): void {
        switching = orgId;
        router.post(
            organizations.switch.url(orgId),
            {},
            {
                onFinish: () => {
                    switching = null;
                },
            },
        );
    }

    function roleLabel(role: string): string {
        return role.charAt(0).toUpperCase() + role.slice(1);
    }
</script>

<AppHead title="Organizations" />

<AppLayout {breadcrumbs}>
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold">Organizations</h2>
        </div>

        {#if orgList.length === 0}
            <div
                class="flex flex-1 items-center justify-center rounded-xl border border-dashed border-sidebar-border p-12"
            >
                <p class="text-muted-foreground">
                    You don't belong to any organization yet.
                </p>
            </div>
        {:else}
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                {#each orgList as org (org.id)}
                    {@const isCurrent = org.id === currentOrganizationId}
                    {@const isOwner = org.role === 'owner'}
                    <Card class={isCurrent ? 'border-primary' : ''}>
                        <CardHeader>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <CardTitle class="text-base">
                                        {org.name}
                                    </CardTitle>
                                    {#if isOwner}
                                        <Crown class="size-4 text-amber-500" />
                                    {/if}
                                </div>
                                {#if isCurrent}
                                    <span
                                        class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary"
                                    >
                                        <Check class="size-3" />
                                        Active
                                    </span>
                                {/if}
                            </div>
                        </CardHeader>
                        <CardContent class="space-y-1">
                            <p class="text-xs text-muted-foreground">
                                {org.projects_count}
                                {org.projects_count === 1
                                    ? 'project'
                                    : 'projects'}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                Your role: <span class="font-medium"
                                    >{roleLabel(org.role)}</span
                                >
                            </p>
                        </CardContent>
                        <CardFooter>
                            {#if isCurrent}
                                <Button
                                    variant="outline"
                                    size="sm"
                                    class="w-full"
                                    disabled
                                >
                                    Currently active
                                </Button>
                            {:else}
                                <Button
                                    variant="default"
                                    size="sm"
                                    class="w-full"
                                    onclick={() => switchOrg(org.id)}
                                    disabled={switching === org.id}
                                >
                                    {switching === org.id
                                        ? 'Switching...'
                                        : 'Switch to this organization'}
                                </Button>
                            {/if}
                        </CardFooter>
                    </Card>
                {/each}
            </div>
        {/if}
    </div>
</AppLayout>
