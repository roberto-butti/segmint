<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import { Button } from '@/components/ui/button';
    import {
        Card,
        CardContent,
        CardFooter,
        CardHeader,
        CardTitle,
    } from '@/components/ui/card';
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
    import projects from '@/routes/projects';
    import ruleTemplatesRoute from '@/routes/projects/rule-templates';
    import type { BreadcrumbItem } from '@/types';

    interface Project {
        id: number;
        name: string;
        slug: string;
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
        templates,
        ruleTypes,
        ruleOperators,
    }: {
        project: Project;
        templates: RuleTemplateItem[];
        ruleTypes: EnumOption[];
        ruleOperators: EnumOption[];
    } = $props();

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
            title: 'Rule Templates',
            href: ruleTemplatesRoute.index.url(project.slug),
        },
    ];

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
    let createType = $state(ruleTypes[0]?.value ?? '');
    let createKey = $state('');
    let createOperator = $state(ruleOperators[0]?.value ?? '');
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
            ruleTemplatesRoute.store.url(project.slug),
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
                project: project.slug,
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
                project: project.slug,
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

<AppHead title={`Rule Templates - ${project.name}`} />

<AppLayout {breadcrumbs}>
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold">Rule Templates</h2>
            <Dialog bind:open={createOpen}>
                <DialogTrigger>
                    <Button variant="default" size="sm">Create template</Button>
                </DialogTrigger>
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
                                    >{getTypeLabel(createType)}</SelectTrigger
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
                        <DialogClose>
                            <Button variant="outline">Cancel</Button>
                        </DialogClose>
                        <Button
                            onclick={handleCreate}
                            disabled={createProcessing || !createName.trim()}
                        >
                            {createProcessing ? 'Creating...' : 'Create'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>

        {#if templates.length === 0}
            <div
                class="flex flex-1 items-center justify-center rounded-xl border border-dashed border-sidebar-border p-12"
            >
                <p class="text-muted-foreground">
                    No rule templates yet. Create one to speed up segment
                    building.
                </p>
            </div>
        {:else}
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                {#each templates as template (template.id)}
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-base"
                                >{template.name}</CardTitle
                            >
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
                <DialogClose>
                    <Button variant="outline">Cancel</Button>
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
