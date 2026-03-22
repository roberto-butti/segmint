<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import {
        Select,
        SelectContent,
        SelectItem,
        SelectTrigger,
    } from '@/components/ui/select';
    import AppLayout from '@/layouts/AppLayout.svelte';
    import projects from '@/routes/projects';
    import type { BreadcrumbItem } from '@/types';

    interface OrganizationOption {
        id: number;
        name: string;
    }

    let { organizations }: { organizations: OrganizationOption[] } = $props();

    let selectedOrgId = $state(organizations[0]?.id?.toString() ?? '');

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

    function getOrgName(id: string): string {
        return (
            organizations.find((o) => o.id.toString() === id)?.name ??
            'Select organization'
        );
    }
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
                {#if organizations.length > 1}
                    <div class="grid gap-2">
                        <Label>Organization</Label>
                        <Select
                            type="single"
                            value={selectedOrgId}
                            onValueChange={(v) => {
                                if (v) {
                                    selectedOrgId = v;
                                }
                            }}
                        >
                            <SelectTrigger class="w-full">
                                {getOrgName(selectedOrgId)}
                            </SelectTrigger>
                            <SelectContent>
                                {#each organizations as org (org.id)}
                                    <SelectItem value={org.id.toString()}
                                        >{org.name}</SelectItem
                                    >
                                {/each}
                            </SelectContent>
                        </Select>
                        <input
                            type="hidden"
                            name="organization_id"
                            value={selectedOrgId}
                        />
                        <InputError message={errors.organization_id} />
                    </div>
                {:else}
                    <input
                        type="hidden"
                        name="organization_id"
                        value={selectedOrgId}
                    />
                {/if}

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
