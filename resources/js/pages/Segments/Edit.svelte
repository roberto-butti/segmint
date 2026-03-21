<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import InputError from '@/components/InputError.svelte';
    import RuleBuilder from '@/components/RuleBuilder.svelte';
    import { Button } from '@/components/ui/button';
    import { Checkbox } from '@/components/ui/checkbox';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import AppLayout from '@/layouts/AppLayout.svelte';
    import projects from '@/routes/projects';
    import segments from '@/routes/projects/segments';
    import type { BreadcrumbItem } from '@/types';

    interface Project {
        id: number;
        name: string;
        slug: string;
    }

    interface SegmentRule {
        id: number;
        type: string;
        key: string;
        operator: string;
        value: string;
        priority: number;
    }

    interface Segment {
        id: number;
        name: string;
        slug: string;
        description: string | null;
        active: boolean;
        rules: SegmentRule[];
    }

    interface EnumOption {
        value: string;
        label: string;
    }

    interface RuleTemplateItem {
        id: number;
        name: string;
        type: string;
        key: string;
        operator: string;
        value: string;
    }

    let {
        project,
        segment,
        ruleTypes,
        ruleOperators,
        ruleTemplates = [],
    }: {
        project: Project;
        segment: Segment;
        ruleTypes: EnumOption[];
        ruleOperators: EnumOption[];
        ruleTemplates?: RuleTemplateItem[];
    } = $props();

    let isActive = $state(segment.active);
    let rules = $state(
        segment.rules.map((r) => ({
            type: r.type,
            key: r.key,
            operator: r.operator,
            value: r.value,
            priority: r.priority,
        })),
    );

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
            title: 'Segments',
            href: segments.index.url(project.slug),
        },
        {
            title: segment.name,
            href: segments.edit.url({
                project: project.slug,
                segment: segment.id,
            }),
        },
    ];
</script>

<AppHead title={`Edit ${segment.name}`} />

<AppLayout {breadcrumbs}>
    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
        <Heading
            variant="small"
            title="Edit segment"
            description="Update your segment details and rules"
        />

        <Form
            action={segments.update.url({
                project: project.slug,
                segment: segment.id,
            })}
            method="put"
            class="max-w-2xl space-y-6"
            options={{ preserveScroll: true }}
        >
            {#snippet children({ errors, processing, recentlySuccessful })}
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input
                        id="name"
                        name="name"
                        class="mt-1 block w-full"
                        value={segment.name}
                        required
                        placeholder="Segment name"
                    />
                    <InputError message={errors.name} />
                </div>

                <div class="grid gap-2">
                    <Label for="description">Description</Label>
                    <Input
                        id="description"
                        name="description"
                        class="mt-1 block w-full"
                        value={segment.description ?? ''}
                        placeholder="A short description of the segment"
                    />
                    <InputError message={errors.description} />
                </div>

                <div class="flex items-center space-x-3">
                    <Checkbox id="active" bind:checked={isActive} />
                    <Label for="active">Active</Label>
                    <InputError message={errors.active} />
                </div>

                <input
                    type="hidden"
                    name="active"
                    value={isActive ? '1' : '0'}
                />

                <RuleBuilder
                    bind:rules
                    {ruleTypes}
                    {ruleOperators}
                    {ruleTemplates}
                    {errors}
                />

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
