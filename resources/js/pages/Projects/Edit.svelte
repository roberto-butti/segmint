<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Checkbox } from '@/components/ui/checkbox';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import AppLayout from '@/layouts/AppLayout.svelte';
    import projects from '@/routes/projects';
    import type { BreadcrumbItem } from '@/types';

    interface Project {
        id: number;
        name: string;
        slug: string;
        description: string | null;
        active: boolean;
    }

    let { project }: { project: Project } = $props();

    let isActive = $state(project.active);

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
            title: 'Edit',
            href: projects.edit.url(project.slug),
        },
    ];
</script>

<AppHead title={`Edit ${project.name}`} />

<AppLayout {breadcrumbs}>
    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
        <Heading
            variant="small"
            title="Edit project"
            description="Update your project details"
        />

        <Form
            action={projects.update.url(project.slug)}
            method="put"
            class="max-w-lg space-y-6"
            options={{ preserveScroll: true }}
        >
            {#snippet children({ errors, processing, recentlySuccessful })}
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input
                        id="name"
                        name="name"
                        class="mt-1 block w-full"
                        value={project.name}
                        required
                        placeholder="Project name"
                    />
                    <InputError message={errors.name} />
                </div>

                <div class="grid gap-2">
                    <Label for="description">Description</Label>
                    <Input
                        id="description"
                        name="description"
                        class="mt-1 block w-full"
                        value={project.description ?? ''}
                        placeholder="A short description of the project"
                    />
                    <InputError message={errors.description} />
                </div>

                <div class="flex items-center space-x-3">
                    <Checkbox
                        id="active"
                        bind:checked={isActive}
                    />
                    <Label for="active">Active</Label>
                    <InputError message={errors.active} />
                </div>

                <input type="hidden" name="active" value={isActive ? '1' : '0'} />

                <div class="flex items-center gap-4">
                    <Button type="submit" disabled={processing}>
                        Save changes
                    </Button>

                    {#if recentlySuccessful}
                        <p class="text-sm text-neutral-600">Saved.</p>
                    {/if}
                </div>
            {/snippet}
        </Form>
    </div>
</AppLayout>
