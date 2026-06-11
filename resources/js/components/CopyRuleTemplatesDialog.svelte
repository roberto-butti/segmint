<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import { untrack } from 'svelte';
    import { Alert, AlertDescription } from '@/components/ui/alert';
    import { Button } from '@/components/ui/button';
    import {
        Dialog,
        DialogClose,
        DialogContent,
        DialogDescription,
        DialogFooter,
        DialogTitle,
        DialogTrigger,
    } from '@/components/ui/dialog';
    import {
        Select,
        SelectContent,
        SelectItem,
        SelectTrigger,
    } from '@/components/ui/select';
    import ruleTemplates from '@/routes/projects/rule-templates';

    interface RuleTemplate {
        id: number;
        name: string;
    }

    interface DestinationProject {
        id: number;
        name: string;
        slug: string;
        organization_name: string;
        rule_template_names: string[];
    }

    let {
        sourceProjectSlug,
        selectedTemplates,
        destinationProjects,
    }: {
        sourceProjectSlug: string;
        selectedTemplates: RuleTemplate[];
        destinationProjects: DestinationProject[];
    } = $props();

    let open = $state(false);
    let destinationProjectId = $state('');
    let processing = $state(false);

    const destinationProject = $derived(
        destinationProjects.find(
            (project) => project.id.toString() === destinationProjectId,
        ),
    );
    const conflictingTemplates = $derived(
        destinationProject
            ? selectedTemplates.filter((template) =>
                  destinationProject.rule_template_names.includes(
                      template.name,
                  ),
              )
            : [],
    );
    const copyableCount = $derived(
        selectedTemplates.length - conflictingTemplates.length,
    );

    $effect(() => {
        if (open && !destinationProjectId && destinationProjects.length > 0) {
            untrack(() => {
                destinationProjectId = destinationProjects[0].id.toString();
            });
        }
    });

    function handleCopy(): void {
        if (!destinationProject || copyableCount === 0) {
            return;
        }

        processing = true;
        router.post(
            ruleTemplates.copy.url(sourceProjectSlug),
            {
                destination_project_id: destinationProject.id,
                rule_template_ids: selectedTemplates.map(
                    (template) => template.id,
                ),
            },
            {
                onSuccess: () => {
                    open = false;
                },
                onFinish: () => {
                    processing = false;
                },
            },
        );
    }
</script>

<Dialog bind:open>
    <DialogTrigger asChild>
        {#snippet children(props)}
            <Button
                variant="outline"
                size="sm"
                onclick={props.onclick}
                aria-expanded={props['aria-expanded']}
                disabled={selectedTemplates.length === 0 ||
                    destinationProjects.length === 0}
            >
                Copy to project
                {#if selectedTemplates.length > 0}
                    ({selectedTemplates.length})
                {/if}
            </Button>
        {/snippet}
    </DialogTrigger>
    <DialogContent>
        <DialogTitle>Copy rule templates to another project</DialogTitle>
        <DialogDescription>
            Copy the selected reusable rule templates without overwriting
            templates that already exist.
        </DialogDescription>

        <div class="grid gap-4 py-4">
            <div class="grid gap-2">
                <span class="text-sm font-medium">Destination project</span>
                <Select bind:value={destinationProjectId}>
                    <SelectTrigger class="w-full">
                        {#if destinationProject}
                            {destinationProject.name}
                            <span class="text-muted-foreground">
                                ({destinationProject.organization_name})
                            </span>
                        {:else}
                            Select a project
                        {/if}
                    </SelectTrigger>
                    <SelectContent>
                        {#each destinationProjects as project (project.id)}
                            <SelectItem value={project.id.toString()}>
                                {project.name} ({project.organization_name})
                            </SelectItem>
                        {/each}
                    </SelectContent>
                </Select>
            </div>

            <div class="rounded-lg border p-3 text-sm">
                <p>
                    <strong>{copyableCount}</strong>
                    rule {copyableCount === 1 ? 'template' : 'templates'} will be
                    copied.
                </p>
            </div>

            {#if conflictingTemplates.length > 0}
                <Alert>
                    <AlertDescription>
                        <p>
                            {conflictingTemplates.length} rule
                            {conflictingTemplates.length === 1
                                ? 'template has'
                                : 'templates have'}
                            a name that already exists and will be skipped:
                        </p>
                        <ul class="list-disc pl-5">
                            {#each conflictingTemplates as template (template.id)}
                                <li>{template.name}</li>
                            {/each}
                        </ul>
                    </AlertDescription>
                </Alert>
            {/if}
        </div>

        <DialogFooter>
            <DialogClose asChild>
                {#snippet children(props)}
                    <Button variant="outline" onclick={props.onclick}>
                        Cancel
                    </Button>
                {/snippet}
            </DialogClose>
            <Button
                onclick={handleCopy}
                disabled={processing ||
                    !destinationProject ||
                    copyableCount === 0}
            >
                {processing
                    ? 'Copying...'
                    : `Copy ${copyableCount} rule ${copyableCount === 1 ? 'template' : 'templates'}`}
            </Button>
        </DialogFooter>
    </DialogContent>
</Dialog>
