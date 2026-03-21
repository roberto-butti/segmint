<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import AppLayout from '@/layouts/AppLayout.svelte';
    import projects from '@/routes/projects';
    import type { BreadcrumbItem } from '@/types';

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Projects',
            href: projects.index.url(),
        },
        {
            title: 'Create',
            href: projects.create.url(),
        },
    ];
</script>

<AppHead title="Create project" />

<AppLayout {breadcrumbs}>
    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
        <Heading
            variant="small"
            title="Create project"
            description="Set up a new project to start tracking events and defining segments"
        />

        <Form
            action={projects.store.url()}
            method="post"
            class="max-w-lg space-y-6"
        >
            {#snippet children({ errors, processing })}
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input
                        id="name"
                        name="name"
                        class="mt-1 block w-full"
                        required
                        placeholder="My project"
                    />
                    <InputError message={errors.name} />
                </div>

                <div class="grid gap-2">
                    <Label for="description">Description</Label>
                    <Input
                        id="description"
                        name="description"
                        class="mt-1 block w-full"
                        placeholder="A short description of the project"
                    />
                    <InputError message={errors.description} />
                </div>

                <p class="text-sm text-muted-foreground">
                    A default access token and rule templates will be created
                    automatically.
                </p>

                <div class="flex items-center gap-4">
                    <Button type="submit" disabled={processing}>
                        Create project
                    </Button>
                </div>
            {/snippet}
        </Form>
    </div>
</AppLayout>
