<script lang="ts">
    import { Link } from '@inertiajs/svelte';
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

    let { projectsCount }: { projectsCount: number } = $props();

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
        <div class="grid auto-rows-min gap-4 md:grid-cols-3">
            <Card>
                <CardHeader>
                    <CardTitle class="text-sm font-medium">Projects</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="text-3xl font-bold">{projectsCount}</div>
                    <p class="text-xs text-muted-foreground">
                        Total projects available
                    </p>
                </CardContent>
                <CardFooter>
                    <Button variant="outline" size="sm" class="w-full">
                        <Link href={projects.index.url()}
                            >View all projects</Link
                        >
                    </Button>
                </CardFooter>
            </Card>
        </div>
    </div>
</AppLayout>
