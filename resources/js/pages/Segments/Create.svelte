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
    import type { BreadcrumbOrganization } from '@/lib/breadcrumbs';
    import { projectBreadcrumbs } from '@/lib/breadcrumbs';
    import segments from '@/routes/projects/segments';
    import type { BreadcrumbItem } from '@/types';

    interface Project {
        id: number;
        name: string;
        public_id: string;
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
        organization,
        ruleTypes,
        ruleOperators,
        ruleTemplates = [],
    }: {
        project: Project;
        organization: BreadcrumbOrganization;
        ruleTypes: EnumOption[];
        ruleOperators: EnumOption[];
        ruleTemplates?: RuleTemplateItem[];
    } = $props();

    let isActive = $state(true);
    let segmentName = $state('');
    let rules = $state<
        {
            type: string;
            key: string;
            operator: string;
            value: string;
            priority: number;
        }[]
    >([]);

    const breadcrumbs: BreadcrumbItem[] = $derived([
        ...projectBreadcrumbs(organization, project),
        {
            title: 'Segments',
            href: segments.index.url(project.public_id),
        },
        {
            title: 'Create',
            href: segments.create.url(project.public_id),
        },
    ]);

    function suggestNameFromTemplate(template: RuleTemplateItem): void {
        if (segmentName.trim() === '') {
            segmentName = template.name;
        }
    }
</script>

<AppHead title={`Create segment - ${project.name}`} />

<AppLayout {breadcrumbs}>
    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
        <Heading
            variant="small"
            title="Create segment"
            description="Add a new segment to your project"
        />

        <Form
            action={segments.store.url(project.public_id)}
            method="post"
            class="max-w-2xl space-y-6"
        >
            {#snippet children({ errors, processing })}
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input
                        id="name"
                        name="name"
                        class="mt-1 block w-full"
                        bind:value={segmentName}
                        required
                        placeholder="Segment name"
                    />
                    <InputError message={errors.name} />
                    <InputError message={errors.slug} />
                </div>

                <div class="grid gap-2">
                    <Label for="description">Description</Label>
                    <Input
                        id="description"
                        name="description"
                        class="mt-1 block w-full"
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
                    onTemplateSelected={suggestNameFromTemplate}
                />

                <div class="flex items-center gap-4">
                    <Button type="submit" disabled={processing}>
                        Create segment
                    </Button>
                </div>
            {/snippet}
        </Form>
    </div>
</AppLayout>
