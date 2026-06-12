<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import Building2 from 'lucide-svelte/icons/building-2';
    import FolderKanban from 'lucide-svelte/icons/folder-kanban';
    import Users from 'lucide-svelte/icons/users';
    import AppHead from '@/components/AppHead.svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import {
        Card,
        CardContent,
        CardFooter,
        CardHeader,
        CardTitle,
    } from '@/components/ui/card';
    import AppLayout from '@/layouts/AppLayout.svelte';
    import { dashboard } from '@/routes';
    import organizations from '@/routes/organizations';
    import organizationProjects from '@/routes/organizations/projects';
    import type { BreadcrumbItem } from '@/types';

    interface Organization {
        id: number;
        public_id: string;
        name: string;
        role: string;
        projects_count: number;
        active_projects_count: number;
        members_count: number;
    }

    let { organizations: organizationList }: { organizations: Organization[] } =
        $props();

    const ownedOrganization = $derived(
        organizationList.find((organization) => organization.role === 'owner'),
    );
    const otherOrganizations = $derived(
        organizationList.filter(
            (organization) => organization.role !== 'owner',
        ),
    );

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Organizations',
            href: dashboard(),
        },
    ];

    function roleLabel(role: string): string {
        return role.charAt(0).toUpperCase() + role.slice(1);
    }
</script>

{#snippet organizationCard(organization: Organization)}
    <Card class="flex flex-col">
        <CardHeader>
            <div class="flex items-start justify-between gap-3">
                <div class="flex min-w-0 items-center gap-2">
                    <Building2 class="size-4 shrink-0 text-muted-foreground" />
                    <CardTitle class="truncate text-base">
                        {organization.name}
                    </CardTitle>
                </div>
                <Badge variant="secondary">
                    {roleLabel(organization.role)}
                </Badge>
            </div>
        </CardHeader>
        <CardContent class="flex flex-1 flex-col gap-3">
            <div class="flex items-center gap-2 text-sm">
                <FolderKanban class="size-4 text-muted-foreground" />
                <span>
                    {organization.projects_count}
                    {organization.projects_count === 1 ? 'project' : 'projects'}
                </span>
                <span class="text-muted-foreground">
                    ({organization.active_projects_count} active)
                </span>
            </div>
            <div class="flex items-center gap-2 text-sm">
                <Users class="size-4 text-muted-foreground" />
                <span>
                    {organization.members_count}
                    {organization.members_count === 1 ? 'member' : 'members'}
                </span>
            </div>
        </CardContent>
        <CardFooter class="grid grid-cols-2 gap-2">
            <Button size="sm" asChild>
                {#snippet children(props)}
                    <Link
                        href={organizations.dashboard.url(
                            organization.public_id,
                        )}
                        class={props.class}
                    >
                        Open dashboard
                    </Link>
                {/snippet}
            </Button>
            <Button variant="outline" size="sm" asChild>
                {#snippet children(props)}
                    <Link
                        href={organizationProjects.index.url(
                            organization.public_id,
                        )}
                        class={props.class}
                    >
                        View projects
                    </Link>
                {/snippet}
            </Button>
        </CardFooter>
    </Card>
{/snippet}

<AppHead title="Organizations" />

<AppLayout {breadcrumbs}>
    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
        <div>
            <h2 class="text-xl font-semibold">Organizations</h2>
            <p class="mt-1 text-sm text-muted-foreground">
                Select an organization to view its dashboard and projects.
            </p>
        </div>

        {#if organizationList.length === 0}
            <div
                class="flex flex-1 items-center justify-center rounded-xl border border-dashed border-sidebar-border p-12"
            >
                <div class="text-center">
                    <Building2
                        class="mx-auto mb-3 size-8 text-muted-foreground"
                    />
                    <p class="font-medium">No organizations available</p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Create or join an organization to manage projects.
                    </p>
                </div>
            </div>
        {:else}
            {#if ownedOrganization}
                <section class="space-y-3">
                    <h3 class="text-sm font-medium text-muted-foreground">
                        Owned organization
                    </h3>
                    <div
                        class="grid auto-rows-fr gap-4 md:grid-cols-2 xl:grid-cols-3"
                    >
                        {@render organizationCard(ownedOrganization)}
                    </div>
                </section>
            {/if}

            {#if otherOrganizations.length > 0}
                <section class="space-y-3">
                    <h3 class="text-sm font-medium text-muted-foreground">
                        Other organizations
                    </h3>
                    <div
                        class="grid auto-rows-fr gap-4 md:grid-cols-2 xl:grid-cols-3"
                    >
                        {#each otherOrganizations as organization (organization.id)}
                            {@render organizationCard(organization)}
                        {/each}
                    </div>
                </section>
            {/if}
        {/if}
    </div>
</AppLayout>
