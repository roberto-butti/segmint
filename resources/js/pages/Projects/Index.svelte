<script lang="ts">
    import { Link, router } from '@inertiajs/svelte';
    import Building2 from 'lucide-svelte/icons/building-2';
    import AppHead from '@/components/AppHead.svelte';
    import { Button } from '@/components/ui/button';
    import {
        Card,
        CardContent,
        CardDescription,
        CardHeader,
        CardTitle,
    } from '@/components/ui/card';
    import {
        Select,
        SelectContent,
        SelectItem,
        SelectTrigger,
    } from '@/components/ui/select';
    import AppLayout from '@/layouts/AppLayout.svelte';
    import projects from '@/routes/projects';
    import type { BreadcrumbItem } from '@/types';

    interface Project {
        id: number;
        name: string;
        slug: string;
        description: string | null;
        active: boolean;
        created_at: string;
    }

    interface OrganizationOption {
        id: number;
        name: string;
        role: string;
    }

    let {
        organizations,
        selectedOrganizationId,
        projects: projectList,
    }: {
        organizations: OrganizationOption[];
        selectedOrganizationId: number | null;
        projects: Project[];
    } = $props();

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Projects',
            href: projects.index.url(),
        },
    ];

    function roleLabel(role: string): string {
        return role.charAt(0).toUpperCase() + role.slice(1);
    }

    function getOrgLabel(id: string): string {
        const org = organizations.find((o) => o.id.toString() === id);

        if (!org) {
            return 'Select organization';
        }

        return `${org.name} (${roleLabel(org.role)})`;
    }

    function onOrgChange(value: string | undefined): void {
        if (!value) {
            return;
        }

        router.get(
            projects.index.url(),
            { organization_id: value },
            { preserveState: false },
        );
    }

    const selectedValue = $derived(selectedOrganizationId?.toString() ?? '');
    const hasSelection = $derived(selectedOrganizationId !== null);
</script>

<AppHead title="Projects" />

<AppLayout {breadcrumbs}>
    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <h2 class="text-xl font-semibold">Projects</h2>
                <div class="w-64">
                    <Select
                        type="single"
                        value={selectedValue}
                        onValueChange={onOrgChange}
                    >
                        <SelectTrigger class="w-full">
                            <div class="flex items-center gap-2">
                                <Building2
                                    class="size-4 text-muted-foreground"
                                />
                                <span class="truncate">
                                    {hasSelection
                                        ? getOrgLabel(selectedValue)
                                        : 'Select organization'}
                                </span>
                            </div>
                        </SelectTrigger>
                        <SelectContent>
                            {#each organizations as org (org.id)}
                                <SelectItem value={org.id.toString()}>
                                    {org.name}
                                    <span class="ml-1 text-muted-foreground"
                                        >({roleLabel(org.role)})</span
                                    >
                                </SelectItem>
                            {/each}
                        </SelectContent>
                    </Select>
                </div>
            </div>
            {#if hasSelection}
                <Button variant="default" size="sm">
                    <Link href={projects.create.url()}>Create project</Link>
                </Button>
            {/if}
        </div>

        {#if !hasSelection}
            <div
                class="flex flex-1 items-center justify-center rounded-xl border border-dashed border-sidebar-border p-12"
            >
                <p class="text-muted-foreground">
                    Select an organization to see its projects.
                </p>
            </div>
        {:else if projectList.length === 0}
            <div
                class="flex flex-1 items-center justify-center rounded-xl border border-dashed border-sidebar-border p-12"
            >
                <p class="text-muted-foreground">
                    No projects in this organization yet.
                </p>
            </div>
        {:else}
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                {#each projectList as project (project.id)}
                    <Link href={projects.show.url(project.slug)} class="block">
                        <Card
                            class="transition-colors hover:border-foreground/20"
                        >
                            <CardHeader>
                                <div class="flex items-center justify-between">
                                    <CardTitle class="text-base">
                                        {project.name}
                                    </CardTitle>
                                    <span
                                        class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {project.active
                                            ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300'
                                            : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'}"
                                    >
                                        {project.active ? 'Active' : 'Inactive'}
                                    </span>
                                </div>
                                {#if project.description}
                                    <CardDescription>
                                        {project.description}
                                    </CardDescription>
                                {/if}
                            </CardHeader>
                            <CardContent>
                                <p class="text-xs text-muted-foreground">
                                    {project.slug}
                                </p>
                            </CardContent>
                        </Card>
                    </Link>
                {/each}
            </div>
        {/if}
    </div>
</AppLayout>
