<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import Building2 from 'lucide-svelte/icons/building-2';
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
    import { dashboard } from '@/routes';
    import projects from '@/routes/projects';
    import type { BreadcrumbItem } from '@/types';

    interface OwnedOrganization {
        id: number;
        name: string;
        projectsCount: number;
    }

    let {
        projectsCount,
        organizationsCount,
        ownedOrganization,
    }: {
        projectsCount: number;
        organizationsCount: number;
        ownedOrganization: OwnedOrganization | null;
    } = $props();

    const invitedCount = $derived(
        ownedOrganization ? organizationsCount - 1 : organizationsCount,
    );

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ];
</script>

<AppHead title="Dashboard" />

<AppLayout {breadcrumbs}>
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
        <div class="grid auto-rows-fr gap-4 md:grid-cols-3">
            <Card class="flex flex-col">
                <CardHeader>
                    <CardTitle class="text-sm font-medium">Projects</CardTitle>
                </CardHeader>
                <CardContent class="flex-1">
                    <div class="text-3xl font-bold">{projectsCount}</div>
                    <p class="text-xs text-muted-foreground">
                        Total projects across all organizations
                    </p>
                </CardContent>
                <CardFooter>
                    <Button variant="outline" size="sm" class="w-full">
                        <Link href={projects.index.url()}>View projects</Link>
                    </Button>
                </CardFooter>
            </Card>

            {#if ownedOrganization}
                <Card class="flex flex-col">
                    <CardHeader>
                        <div class="flex min-w-0 items-center gap-2">
                            <Crown class="size-4 shrink-0 text-amber-500" />
                            <CardTitle class="truncate text-sm font-medium"
                                >Your Organization</CardTitle
                            >
                        </div>
                    </CardHeader>
                    <CardContent class="flex-1">
                        <div class="text-3xl font-bold">
                            {ownedOrganization.projectsCount}
                        </div>
                        <p class="text-xs text-muted-foreground">
                            {ownedOrganization.projectsCount === 1
                                ? 'project'
                                : 'projects'} in {ownedOrganization.name}
                        </p>
                    </CardContent>
                    <CardFooter>
                        <Button variant="outline" size="sm" class="w-full">
                            <Link
                                href={projects.index.url({
                                    query: {
                                        organization_id: ownedOrganization.id,
                                    },
                                })}
                            >
                                View projects
                            </Link>
                        </Button>
                    </CardFooter>
                </Card>
            {:else}
                <Card class="flex flex-col">
                    <CardHeader>
                        <div class="flex items-center gap-2">
                            <Building2 class="size-4 text-muted-foreground" />
                            <CardTitle class="text-sm font-medium"
                                >Organization</CardTitle
                            >
                        </div>
                    </CardHeader>
                    <CardContent class="flex-1">
                        <p class="text-sm text-muted-foreground">
                            You don't own an organization yet. Create one to
                            start managing your own projects and invite team
                            members.
                        </p>
                    </CardContent>
                    <CardFooter>
                        <Button variant="default" size="sm" class="w-full">
                            Create organization
                        </Button>
                    </CardFooter>
                </Card>
            {/if}

            <Card class="flex flex-col">
                <CardHeader>
                    <div class="flex items-center gap-2">
                        <Building2 class="size-4 text-muted-foreground" />
                        <CardTitle class="text-sm font-medium"
                            >Invited to</CardTitle
                        >
                    </div>
                </CardHeader>
                <CardContent class="flex-1">
                    <div class="text-3xl font-bold">{invitedCount}</div>
                    <p class="text-xs text-muted-foreground">
                        other {invitedCount === 1
                            ? 'organization'
                            : 'organizations'}
                    </p>
                </CardContent>
                <CardFooter>
                    <Button variant="outline" size="sm" class="w-full">
                        <Link href={projects.index.url()}>View projects</Link>
                    </Button>
                </CardFooter>
            </Card>
        </div>
    </div>
</AppLayout>
