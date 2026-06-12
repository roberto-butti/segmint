<script lang="ts">
    import { Link, page, router } from '@inertiajs/svelte';
    import X from 'lucide-svelte/icons/x';
    import { untrack } from 'svelte';
    import AppHead from '@/components/AppHead.svelte';
    import CopyRuleTemplatesDialog from '@/components/CopyRuleTemplatesDialog.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import { Alert, AlertDescription } from '@/components/ui/alert';
    import { Button } from '@/components/ui/button';
    import {
        Card,
        CardContent,
        CardFooter,
        CardHeader,
        CardTitle,
    } from '@/components/ui/card';
    import { Checkbox } from '@/components/ui/checkbox';
    import {
        Dialog,
        DialogClose,
        DialogContent,
        DialogDescription,
        DialogFooter,
        DialogTitle,
        DialogTrigger,
    } from '@/components/ui/dialog';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import {
        Select,
        SelectContent,
        SelectItem,
        SelectTrigger,
    } from '@/components/ui/select';
    import AppLayout from '@/layouts/AppLayout.svelte';
    import { projectBreadcrumbs } from '@/lib/breadcrumbs';
    import type { BreadcrumbOrganization } from '@/lib/breadcrumbs';
    import ruleTemplatesRoute from '@/routes/projects/rule-templates';
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

    interface DestinationProject {
        id: number;
        name: string;
        public_id: string;
        organization_name: string;
        rule_template_names: string[];
    }

    let {
        project,
        organization,
        templates,
        destinationProjects,
        ruleTypes,
        ruleOperators,
        canManageProject,
    }: {
        project: Project;
        organization: BreadcrumbOrganization;
        templates: RuleTemplateItem[];
        destinationProjects: DestinationProject[];
        ruleTypes: EnumOption[];
        ruleOperators: EnumOption[];
        canManageProject: boolean;
    } = $props();

    let selected = $state<Record<number, boolean>>({});
    let dismissedCopyMessageId = $state<string | null>(null);

    const selectedTemplates = $derived(
        templates.filter((template) => selected[template.id]),
    );
    const allSelected = $derived(
        templates.length > 0 && selectedTemplates.length === templates.length,
    );
    const ruleTemplateCopy = $derived(page.props.flash.ruleTemplateCopy);

    function selectAll(): void {
        selected = Object.fromEntries(
            templates.map((template) => [template.id, true]),
        );
    }

    function clearSelection(): void {
        selected = {};
    }

    const breadcrumbs: BreadcrumbItem[] = $derived([
        ...projectBreadcrumbs(organization, project),
        {
            title: 'Rule Templates',
            href: ruleTemplatesRoute.index.url(project.public_id),
        },
    ]);

    const keyDefaults: Record<string, string> = {
        browser_language: 'Accept-Language',
        visit_count: 'page-view',
    };

    function shouldShowKey(type: string): boolean {
        return type !== 'page_view_count';
    }

    function getTypeLabel(value: string): string {
        return ruleTypes.find((t) => t.value === value)?.label ?? value;
    }

    function getOperatorLabel(value: string): string {
        return ruleOperators.find((o) => o.value === value)?.label ?? value;
    }

    // Create form state
    let createOpen = $state(false);
    let createName = $state('');
    let createType = $state(untrack(() => ruleTypes[0]?.value ?? ''));
    let createKey = $state('');
    let createOperator = $state(untrack(() => ruleOperators[0]?.value ?? ''));
    let createValue = $state('');
    let createProcessing = $state(false);

    function resetCreateForm(): void {
        createName = '';
        createType = ruleTypes[0]?.value ?? '';
        createKey = '';
        createOperator = ruleOperators[0]?.value ?? '';
        createValue = '';
    }

    function onCreateTypeChange(newType: string): void {
        createType = newType;
        createKey = keyDefaults[newType] ?? '';
    }

    function handleCreate(): void {
        createProcessing = true;
        router.post(
            ruleTemplatesRoute.store.url(project.public_id),
            {
                name: createName,
                type: createType,
                key: createKey,
                operator: createOperator,
                value: createValue,
            },
            {
                onSuccess: () => {
                    createOpen = false;
                    resetCreateForm();
                },
                onFinish: () => {
                    createProcessing = false;
                },
            },
        );
    }

    // Edit form state
    let editOpen = $state(false);
    let editId = $state(0);
    let editName = $state('');
    let editType = $state('');
    let editKey = $state('');
    let editOperator = $state('');
    let editValue = $state('');
    let editProcessing = $state(false);

    function openEdit(template: RuleTemplateItem): void {
        editId = template.id;
        editName = template.name;
        editType = template.type;
        editKey = template.key;
        editOperator = template.operator;
        editValue = template.value;
        editOpen = true;
    }

    function onEditTypeChange(newType: string): void {
        editType = newType;

        if (newType in keyDefaults) {
            editKey = keyDefaults[newType];
        } else if (newType === 'page_view_count') {
            editKey = '';
        }
    }

    function handleUpdate(): void {
        editProcessing = true;
        router.put(
            ruleTemplatesRoute.update.url({
                project: project.public_id,
                ruleTemplate: editId,
            }),
            {
                name: editName,
                type: editType,
                key: editKey,
                operator: editOperator,
                value: editValue,
            },
            {
                onSuccess: () => {
                    editOpen = false;
                },
                onFinish: () => {
                    editProcessing = false;
                },
            },
        );
    }

    // Delete
    let deleteProcessing = $state<number | null>(null);

    function handleDelete(template: RuleTemplateItem): void {
        deleteProcessing = template.id;
        router.delete(
            ruleTemplatesRoute.destroy.url({
                project: project.public_id,
                ruleTemplate: template.id,
            }),
            {
                onFinish: () => {
                    deleteProcessing = null;
                },
            },
        );
    }
</script>

{#snippet createTemplateAction()}
    <Button size="sm" onclick={() => (createOpen = true)}>
        Create template
    </Button>
{/snippet}

<AppHead title={`Rule Templates - ${project.name}`} />

<AppLayout {breadcrumbs}>
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold">Rule Templates</h2>
            <div class="flex items-center gap-2">
                <CopyRuleTemplatesDialog
                    sourceProjectPublicId={project.public_id}
                    {selectedTemplates}
                    {destinationProjects}
                />
                <Dialog bind:open={createOpen}>
                    {#if canManageProject}
                        <DialogTrigger asChild>
                            {#snippet children(props)}
                                <Button
                                    variant="default"
                                    size="sm"
                                    onclick={props.onclick}
                                    aria-expanded={props['aria-expanded']}
                                    >Create template</Button
                                >
                            {/snippet}
                        </DialogTrigger>
                    {/if}
                    <DialogContent>
                        <DialogTitle>Create rule template</DialogTitle>
                        <DialogDescription>
                            Define a reusable rule preset for this project.
                        </DialogDescription>
                        <div class="grid gap-4 py-4">
                            <div class="grid gap-2">
                                <Label for="create-name">Name</Label>
                                <Input
                                    id="create-name"
                                    bind:value={createName}
                                    placeholder="e.g. Google visitors"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label>Type</Label>
                                <Select
                                    type="single"
                                    value={createType}
                                    onValueChange={(v) => {
                                        if (v) {
                                            onCreateTypeChange(v);
                                        }
                                    }}
                                >
                                    <SelectTrigger class="w-full"
                                        >{getTypeLabel(
                                            createType,
                                        )}</SelectTrigger
                                    >
                                    <SelectContent>
                                        {#each ruleTypes as rt (rt.value)}
                                            <SelectItem value={rt.value}
                                                >{rt.label}</SelectItem
                                            >
                                        {/each}
                                    </SelectContent>
                                </Select>
                            </div>
                            {#if shouldShowKey(createType)}
                                <div class="grid gap-2">
                                    <Label for="create-key">Key</Label>
                                    <Input
                                        id="create-key"
                                        bind:value={createKey}
                                        placeholder="e.g. utms.utm_source"
                                        class="font-mono"
                                    />
                                </div>
                            {/if}
                            <div class="grid gap-2">
                                <Label>Operator</Label>
                                <Select
                                    type="single"
                                    value={createOperator}
                                    onValueChange={(v) => {
                                        if (v) {
                                            createOperator = v;
                                        }
                                    }}
                                >
                                    <SelectTrigger class="w-full"
                                        >{getOperatorLabel(
                                            createOperator,
                                        )}</SelectTrigger
                                    >
                                    <SelectContent>
                                        {#each ruleOperators as op (op.value)}
                                            <SelectItem value={op.value}
                                                >{op.label}</SelectItem
                                            >
                                        {/each}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div class="grid gap-2">
                                <Label for="create-value"
                                    >Value <span class="text-muted-foreground"
                                        >(optional)</span
                                    ></Label
                                >
                                <Input
                                    id="create-value"
                                    bind:value={createValue}
                                    placeholder="Leave empty for user to fill"
                                    class="font-mono"
                                />
                            </div>
                        </div>
                        <DialogFooter>
                            <DialogClose asChild>
                                {#snippet children(props)}
                                    <Button
                                        variant="outline"
                                        onclick={props.onclick}>Cancel</Button
                                    >
                                {/snippet}
                            </DialogClose>
                            <Button
                                onclick={handleCreate}
                                disabled={createProcessing ||
                                    !createName.trim()}
                            >
                                {createProcessing ? 'Creating...' : 'Create'}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </div>

        {#if ruleTemplateCopy && dismissedCopyMessageId !== ruleTemplateCopy.id}
            <Alert class="pr-12">
                <AlertDescription>
                    <p>
                        {ruleTemplateCopy.message}
                        <Link
                            href={ruleTemplateCopy.destination_url}
                            class="font-medium text-foreground underline underline-offset-4"
                        >
                            View {ruleTemplateCopy.destination_name} rule templates
                        </Link>
                    </p>
                </AlertDescription>
                <button
                    type="button"
                    class="absolute top-3 right-3 rounded-sm p-1 text-muted-foreground hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    aria-label="Dismiss copy result"
                    onclick={() => {
                        dismissedCopyMessageId = ruleTemplateCopy.id;
                    }}
                >
                    <X class="size-4" />
                </button>
            </Alert>
        {/if}

        {#if templates.length === 0}
            <EmptyState
                title={`No rule templates in ${project.name}`}
                description="Rule templates store reusable rule presets for faster segment creation."
                class="flex-1"
                actions={canManageProject ? createTemplateAction : undefined}
            />
        {:else}
            <div class="flex items-center gap-2">
                <Button
                    variant="ghost"
                    size="sm"
                    onclick={allSelected ? clearSelection : selectAll}
                >
                    {allSelected ? 'Clear selection' : 'Select all'}
                </Button>
                {#if selectedTemplates.length > 0}
                    <span class="text-sm text-muted-foreground">
                        {selectedTemplates.length} rule
                        {selectedTemplates.length === 1
                            ? 'template selected'
                            : 'templates selected'}
                    </span>
                {/if}
                {#if destinationProjects.length === 0}
                    <span class="text-sm text-muted-foreground">
                        No other manageable projects available.
                    </span>
                {/if}
            </div>
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                {#each templates as template (template.id)}
                    <Card>
                        <CardHeader>
                            <div class="flex items-center gap-2">
                                <Checkbox
                                    id={`rule-template-${template.id}`}
                                    aria-label={`Select ${template.name}`}
                                    checked={selected[template.id] ?? false}
                                    onclick={() => {
                                        selected[template.id] = !(
                                            selected[template.id] ?? false
                                        );
                                    }}
                                />
                                <CardTitle class="text-base"
                                    >{template.name}</CardTitle
                                >
                            </div>
                        </CardHeader>
                        <CardContent class="space-y-1">
                            <p class="text-xs">
                                <span
                                    class="rounded bg-muted px-1.5 py-0.5 font-medium"
                                    >{getTypeLabel(template.type)}</span
                                >
                            </p>
                            {#if template.key}
                                <p
                                    class="font-mono text-xs text-muted-foreground"
                                >
                                    {template.key}
                                </p>
                            {/if}
                            <p class="text-xs text-muted-foreground">
                                {getOperatorLabel(template.operator)}
                                {#if template.value}
                                    <span class="font-mono">
                                        {template.value}</span
                                    >
                                {:else}
                                    <span class="italic">
                                        (user fills value)</span
                                    >
                                {/if}
                            </p>
                        </CardContent>
                        <CardFooter class="gap-2">
                            {#if canManageProject}
                                <Button
                                    variant="outline"
                                    size="sm"
                                    class="flex-1"
                                    onclick={() => openEdit(template)}
                                >
                                    Edit
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    class="flex-1 text-destructive hover:text-destructive"
                                    onclick={() => handleDelete(template)}
                                    disabled={deleteProcessing === template.id}
                                >
                                    {deleteProcessing === template.id
                                        ? 'Deleting...'
                                        : 'Delete'}
                                </Button>
                            {/if}
                        </CardFooter>
                    </Card>
                {/each}
            </div>
        {/if}
    </div>

    <!-- Edit dialog -->
    <Dialog bind:open={editOpen}>
        <DialogContent>
            <DialogTitle>Edit rule template</DialogTitle>
            <DialogDescription>Update this rule template.</DialogDescription>
            <div class="grid gap-4 py-4">
                <div class="grid gap-2">
                    <Label for="edit-name">Name</Label>
                    <Input
                        id="edit-name"
                        bind:value={editName}
                        placeholder="Template name"
                    />
                </div>
                <div class="grid gap-2">
                    <Label>Type</Label>
                    <Select
                        type="single"
                        value={editType}
                        onValueChange={(v) => {
                            if (v) {
                                onEditTypeChange(v);
                            }
                        }}
                    >
                        <SelectTrigger class="w-full"
                            >{getTypeLabel(editType)}</SelectTrigger
                        >
                        <SelectContent>
                            {#each ruleTypes as rt (rt.value)}
                                <SelectItem value={rt.value}
                                    >{rt.label}</SelectItem
                                >
                            {/each}
                        </SelectContent>
                    </Select>
                </div>
                {#if shouldShowKey(editType)}
                    <div class="grid gap-2">
                        <Label for="edit-key">Key</Label>
                        <Input
                            id="edit-key"
                            bind:value={editKey}
                            placeholder="e.g. utms.utm_source"
                            class="font-mono"
                        />
                    </div>
                {/if}
                <div class="grid gap-2">
                    <Label>Operator</Label>
                    <Select
                        type="single"
                        value={editOperator}
                        onValueChange={(v) => {
                            if (v) {
                                editOperator = v;
                            }
                        }}
                    >
                        <SelectTrigger class="w-full"
                            >{getOperatorLabel(editOperator)}</SelectTrigger
                        >
                        <SelectContent>
                            {#each ruleOperators as op (op.value)}
                                <SelectItem value={op.value}
                                    >{op.label}</SelectItem
                                >
                            {/each}
                        </SelectContent>
                    </Select>
                </div>
                <div class="grid gap-2">
                    <Label for="edit-value"
                        >Value <span class="text-muted-foreground"
                            >(optional)</span
                        ></Label
                    >
                    <Input
                        id="edit-value"
                        bind:value={editValue}
                        placeholder="Leave empty for user to fill"
                        class="font-mono"
                    />
                </div>
            </div>
            <DialogFooter>
                <DialogClose asChild>
                    {#snippet children(props)}
                        <Button variant="outline" onclick={props.onclick}
                            >Cancel</Button
                        >
                    {/snippet}
                </DialogClose>
                <Button
                    onclick={handleUpdate}
                    disabled={editProcessing || !editName.trim()}
                >
                    {editProcessing ? 'Saving...' : 'Save changes'}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</AppLayout>
