<script lang="ts">
    import type { RequestPayload } from '@inertiajs/core';
    import { router } from '@inertiajs/svelte';
    import Activity from 'lucide-svelte/icons/activity';
    import ChevronDown from 'lucide-svelte/icons/chevron-down';
    import CircleCheck from 'lucide-svelte/icons/circle-check';
    import CircleX from 'lucide-svelte/icons/circle-x';
    import Play from 'lucide-svelte/icons/play';
    import Save from 'lucide-svelte/icons/save';
    import Trash2 from 'lucide-svelte/icons/trash-2';
    import { untrack } from 'svelte';

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
        Dialog,
        DialogClose,
        DialogContent,
        DialogDescription,
        DialogFooter,
        DialogTitle,
    } from '@/components/ui/dialog';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import AppLayout from '@/layouts/AppLayout.svelte';
    import type { BreadcrumbOrganization } from '@/lib/breadcrumbs';
    import { projectBreadcrumbs } from '@/lib/breadcrumbs';
    import diagnosticsRoutes from '@/routes/projects/diagnostics';
    import type { BreadcrumbItem } from '@/types';

    interface Project {
        id: number;
        name: string;
        public_id: string;
    }

    interface DiagnosticsPayload {
        visitor_id: string;
        type: string;
        url: string | null;
        referrer: string | null;
        utms: {
            utm_source: string | null;
            utm_medium: string | null;
            utm_campaign: string | null;
            utm_term: string | null;
            utm_content: string | null;
        };
        accept_language: string | null;
        event_properties: Record<string, unknown>;
        metadata: Record<string, unknown>;
    }

    interface DiagnosticRule {
        id: number;
        type: string;
        type_label: string;
        key: string | null;
        operator: string;
        operator_label: string;
        expected: string;
        actual: unknown;
        passed: boolean;
        priority: number;
        note: string | null;
    }

    interface DiagnosticSegment {
        id: number;
        name: string;
        slug: string;
        description: string | null;
        matched: boolean;
        rules: DiagnosticRule[];
    }

    interface SavedScenario {
        id: number;
        name: string;
        payload: DiagnosticsPayload;
        last_result: DiagnosticSegment[] | null;
        last_run_at: string | null;
    }

    let {
        project,
        organization,
        payload,
        diagnostics = null,
        evaluatedAt = null,
        savedScenarios = [],
    }: {
        project: Project;
        organization: BreadcrumbOrganization;
        payload: DiagnosticsPayload;
        diagnostics: DiagnosticSegment[] | null;
        evaluatedAt: string | null;
        savedScenarios: SavedScenario[];
    } = $props();

    let visitorId = $state(untrack(() => payload.visitor_id));
    let eventType = $state(untrack(() => payload.type));
    let pageUrl = $state(untrack(() => payload.url ?? ''));
    let referrer = $state(untrack(() => payload.referrer ?? ''));
    let utmSource = $state(untrack(() => payload.utms.utm_source ?? ''));
    let utmMedium = $state(untrack(() => payload.utms.utm_medium ?? ''));
    let utmCampaign = $state(untrack(() => payload.utms.utm_campaign ?? ''));
    let acceptLanguage = $state(untrack(() => payload.accept_language ?? ''));
    let eventPropertiesJson = $state(
        untrack(() => formatJson(payload.event_properties)),
    );
    let metadataJson = $state(untrack(() => formatJson(payload.metadata)));
    let jsonError = $state<string | null>(null);
    let isEvaluating = $state(false);
    let scenarioName = $state('');
    let scenarioError = $state<string | null>(null);
    let isSavingScenario = $state(false);
    let isUpdatingScenario = $state(false);
    let saveDialogOpen = $state(false);
    let selectedScenarioId = $state('');
    let runningScenarioId = $state<number | null>(null);
    let deletingScenarioId = $state<number | null>(null);

    const matchedSegments = $derived(
        diagnostics?.filter((segment) => segment.matched) ?? [],
    );

    const selectedScenario = $derived(
        savedScenarios.find(
            (scenario) => String(scenario.id) === selectedScenarioId,
        ) ?? null,
    );

    const breadcrumbs: BreadcrumbItem[] = $derived([
        ...projectBreadcrumbs(organization, project),
        {
            title: 'Diagnostics',
            href: diagnosticsRoutes.index.url(project.public_id),
        },
    ]);

    function submit(): void {
        jsonError = null;

        if (selectedScenario) {
            runScenario(selectedScenario);

            return;
        }

        const payload = currentPayload();

        if (jsonError) {
            return;
        }

        router.post(
            diagnosticsRoutes.evaluate.url(project.public_id),
            payload as unknown as RequestPayload,
            {
                preserveScroll: true,
                preserveState: false,
                onStart: () => {
                    isEvaluating = true;
                },
                onFinish: () => {
                    isEvaluating = false;
                },
            },
        );
    }

    function saveScenario(): void {
        jsonError = null;
        scenarioError = null;

        const name = scenarioName.trim();

        if (name === '') {
            scenarioError = 'Scenario name is required.';

            return;
        }

        const payload = currentPayload();

        if (jsonError) {
            return;
        }

        router.post(
            `/projects/${project.public_id}/diagnostics/scenarios`,
            {
                name,
                payload,
            } as unknown as RequestPayload,
            {
                preserveScroll: true,
                preserveState: false,
                onStart: () => {
                    isSavingScenario = true;
                },
                onFinish: () => {
                    isSavingScenario = false;
                },
                onSuccess: () => {
                    scenarioName = '';
                    saveDialogOpen = false;
                },
            },
        );
    }

    function runScenario(scenario: SavedScenario): void {
        router.post(
            `/projects/${project.public_id}/diagnostics/scenarios/${scenario.id}/run`,
            {},
            {
                preserveScroll: true,
                preserveState: false,
                onStart: () => {
                    runningScenarioId = scenario.id;
                },
                onFinish: () => {
                    runningScenarioId = null;
                },
            },
        );
    }

    function updateScenario(scenario: SavedScenario): void {
        jsonError = null;

        const payload = currentPayload();

        if (jsonError) {
            return;
        }

        router.put(
            `/projects/${project.public_id}/diagnostics/scenarios/${scenario.id}`,
            {
                payload,
            } as unknown as RequestPayload,
            {
                preserveScroll: true,
                preserveState: false,
                onStart: () => {
                    isUpdatingScenario = true;
                },
                onFinish: () => {
                    isUpdatingScenario = false;
                },
            },
        );
    }

    function selectScenario(value: string): void {
        selectedScenarioId = value;
        scenarioError = null;

        if (value === '') {
            return;
        }

        const scenario = savedScenarios.find(
            (candidate) => String(candidate.id) === value,
        );

        if (!scenario) {
            return;
        }

        applyPayload(scenario.payload);
    }

    function startNewDiagnostic(): void {
        selectedScenarioId = '';
    }

    function applyPayload(nextPayload: DiagnosticsPayload): void {
        visitorId = nextPayload.visitor_id;
        eventType = nextPayload.type;
        pageUrl = nextPayload.url ?? '';
        referrer = nextPayload.referrer ?? '';
        utmSource = nextPayload.utms.utm_source ?? '';
        utmMedium = nextPayload.utms.utm_medium ?? '';
        utmCampaign = nextPayload.utms.utm_campaign ?? '';
        acceptLanguage = nextPayload.accept_language ?? '';
        eventPropertiesJson = formatJson(nextPayload.event_properties);
        metadataJson = formatJson(nextPayload.metadata);
    }

    function deleteScenario(scenario: SavedScenario): void {
        if (!confirm(`Delete diagnostic scenario "${scenario.name}"?`)) {
            return;
        }

        router.delete(
            `/projects/${project.public_id}/diagnostics/scenarios/${scenario.id}`,
            {
                preserveScroll: true,
                onStart: () => {
                    deletingScenarioId = scenario.id;
                },
                onFinish: () => {
                    deletingScenarioId = null;
                },
            },
        );
    }

    function currentPayload(): DiagnosticsPayload {
        const eventProperties = parseJson(
            eventPropertiesJson,
            'Event properties',
        );
        const metadata = parseJson(metadataJson, 'Metadata');

        if (jsonError) {
            return payload;
        }

        return {
            visitor_id: visitorId,
            type: eventType,
            url: pageUrl || null,
            referrer: referrer || null,
            utms: {
                utm_source: utmSource || null,
                utm_medium: utmMedium || null,
                utm_campaign: utmCampaign || null,
                utm_term: null,
                utm_content: null,
            },
            accept_language: acceptLanguage || null,
            event_properties: eventProperties,
            metadata,
        };
    }

    function parseJson(value: string, label: string): Record<string, unknown> {
        if (value.trim() === '') {
            return {};
        }

        try {
            const parsed = JSON.parse(value);

            if (
                parsed === null ||
                typeof parsed !== 'object' ||
                Array.isArray(parsed)
            ) {
                jsonError = `${label} must be a JSON object.`;

                return {};
            }

            return parsed as Record<string, unknown>;
        } catch {
            jsonError = `${label} contains invalid JSON.`;

            return {};
        }
    }

    function formatJson(value: Record<string, unknown>): string {
        return Object.keys(value).length === 0
            ? ''
            : JSON.stringify(value, null, 2);
    }

    function formatActual(value: unknown): string {
        if (value === null || value === undefined || value === '') {
            return 'No value';
        }

        if (typeof value === 'object') {
            return JSON.stringify(value);
        }

        return String(value);
    }

    function matchedCount(result: DiagnosticSegment[] | null): number {
        return result?.filter((segment) => segment.matched).length ?? 0;
    }

    function formatRunDate(value: string | null): string {
        if (!value) {
            return 'Never run';
        }

        return new Intl.DateTimeFormat(undefined, {
            dateStyle: 'medium',
            timeStyle: 'short',
        }).format(new Date(value));
    }

    function formatRunTime(value: string | null): string {
        if (!value) {
            return '';
        }

        return new Intl.DateTimeFormat(undefined, {
            dateStyle: 'medium',
            timeStyle: 'medium',
        }).format(new Date(value));
    }
</script>

<AppHead title={`Diagnostics - ${project.name}`} />

<AppLayout {breadcrumbs}>
    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
        <div>
            <h2 class="text-xl font-semibold">Segment diagnostics</h2>
            <p class="mt-1 text-sm text-muted-foreground">
                Evaluate a candidate event against active segments without
                storing the event or changing analytics.
            </p>
        </div>

        <Card>
            <CardContent class="py-4">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-start">
                    <div class="min-w-0 flex-1 space-y-2">
                        <Label for="scenario-selector">Scenario</Label>
                        <select
                            id="scenario-selector"
                            class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            bind:value={selectedScenarioId}
                            onchange={(event) =>
                                selectScenario(event.currentTarget.value)}
                        >
                            <option value="">New diagnostic</option>
                            {#each savedScenarios as scenario (scenario.id)}
                                <option value={String(scenario.id)}>
                                    {scenario.name}
                                </option>
                            {/each}
                        </select>
                        {#if selectedScenario}
                            <p class="text-xs text-muted-foreground">
                                Last result: {matchedCount(
                                    selectedScenario.last_result,
                                )} of {selectedScenario.last_result?.length ??
                                    0} matched · {formatRunDate(
                                    selectedScenario.last_run_at,
                                )}
                            </p>
                        {/if}
                    </div>
                    <div class="flex flex-wrap gap-2 lg:pt-7">
                        <Button
                            type="button"
                            onclick={submit}
                            disabled={isEvaluating ||
                                runningScenarioId !== null}
                        >
                            {#if selectedScenario}
                                <Play class="size-4" />
                                {runningScenarioId === selectedScenario.id
                                    ? 'Running'
                                    : 'Run scenario'}
                            {:else}
                                <Activity class="size-4" />
                                {isEvaluating ? 'Running' : 'Run diagnostic'}
                            {/if}
                        </Button>
                        {#if diagnostics}
                            <Button
                                type="button"
                                variant="outline"
                                onclick={() => {
                                    scenarioError = null;
                                    saveDialogOpen = true;
                                }}
                            >
                                <Save class="size-4" />
                                Save as scenario
                            </Button>
                        {/if}
                        {#if selectedScenario}
                            <Button
                                type="button"
                                variant="outline"
                                onclick={() => updateScenario(selectedScenario)}
                                disabled={isUpdatingScenario}
                            >
                                <Save class="size-4" />
                                {isUpdatingScenario ? 'Saving' : 'Save changes'}
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                onclick={startNewDiagnostic}
                            >
                                New
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                class="text-destructive hover:text-destructive"
                                onclick={() => deleteScenario(selectedScenario)}
                                disabled={deletingScenarioId ===
                                    selectedScenario.id}
                            >
                                <Trash2 class="size-4" />
                                Delete
                            </Button>
                        {/if}
                    </div>
                </div>
            </CardContent>
        </Card>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Candidate event</CardTitle>
                    <CardDescription>
                        This mirrors a dry-run tracking event for {project.name}.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form
                        class="space-y-4"
                        onsubmit={(event) => {
                            event.preventDefault();
                            submit();
                        }}
                    >
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="visitor-id">Visitor ID</Label>
                                <Input
                                    id="visitor-id"
                                    bind:value={visitorId}
                                    required
                                />
                            </div>
                            <div class="space-y-2">
                                <Label for="event-type">Event type</Label>
                                <Input
                                    id="event-type"
                                    bind:value={eventType}
                                    required
                                />
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="page-url">Page URL</Label>
                            <Input
                                id="page-url"
                                type="url"
                                bind:value={pageUrl}
                            />
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="utm-source">UTM source</Label>
                                <Input id="utm-source" bind:value={utmSource} />
                            </div>
                        </div>

                        <details class="group rounded-lg border p-3">
                            <summary
                                class="flex cursor-pointer list-none items-center justify-between text-sm font-medium"
                            >
                                Advanced
                                <ChevronDown
                                    class="size-4 transition group-open:rotate-180"
                                />
                            </summary>
                            <div class="mt-4 space-y-4">
                                <div class="space-y-2">
                                    <Label for="referrer">Referrer URL</Label>
                                    <Input
                                        id="referrer"
                                        type="url"
                                        bind:value={referrer}
                                    />
                                </div>

                                <div class="grid gap-4 md:grid-cols-2">
                                    <div class="space-y-2">
                                        <Label for="utm-medium"
                                            >UTM medium</Label
                                        >
                                        <Input
                                            id="utm-medium"
                                            bind:value={utmMedium}
                                        />
                                    </div>
                                    <div class="space-y-2">
                                        <Label for="utm-campaign"
                                            >UTM campaign</Label
                                        >
                                        <Input
                                            id="utm-campaign"
                                            bind:value={utmCampaign}
                                        />
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <Label for="accept-language"
                                        >Accept-Language</Label
                                    >
                                    <Input
                                        id="accept-language"
                                        placeholder="en-US,en;q=0.9"
                                        bind:value={acceptLanguage}
                                    />
                                    <p class="text-xs text-muted-foreground">
                                        Defaults to the current browser request
                                        header.
                                    </p>
                                </div>

                                <div class="space-y-2">
                                    <Label for="event-properties"
                                        >Event properties JSON</Label
                                    >
                                    <textarea
                                        id="event-properties"
                                        class="min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                        bind:value={eventPropertiesJson}
                                        placeholder={JSON.stringify({
                                            plan: 'pro',
                                        })}
                                    ></textarea>
                                </div>

                                <div class="space-y-2">
                                    <Label for="metadata">Metadata JSON</Label>
                                    <textarea
                                        id="metadata"
                                        class="min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                        bind:value={metadataJson}
                                        placeholder={JSON.stringify({
                                            source: 'diagnostics',
                                        })}
                                    ></textarea>
                                </div>
                            </div>
                        </details>

                        {#if jsonError}
                            <p class="text-sm text-destructive">{jsonError}</p>
                        {/if}

                        <Button
                            type="submit"
                            disabled={isEvaluating ||
                                runningScenarioId !== null}
                        >
                            {#if selectedScenario}
                                <Play class="size-4" />
                                {runningScenarioId === selectedScenario.id
                                    ? 'Running'
                                    : 'Run scenario'}
                            {:else}
                                <Activity class="size-4" />
                                {isEvaluating ? 'Running' : 'Run diagnostic'}
                            {/if}
                        </Button>
                    </form>
                </CardContent>
            </Card>

            <div class="space-y-4">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base"
                            >Evaluation result</CardTitle
                        >
                        <CardDescription>
                            {#if diagnostics}
                                {matchedSegments.length} of {diagnostics.length}
                                active segments matched this candidate event.
                            {:else}
                                Submit a candidate event to see segment and rule
                                results.
                            {/if}
                        </CardDescription>
                    </CardHeader>
                    {#if diagnostics}
                        <CardContent>
                            <div
                                class="grid gap-3 text-sm text-muted-foreground sm:grid-cols-3"
                            >
                                <div>
                                    <p
                                        class="text-xs uppercase text-muted-foreground"
                                    >
                                        Executed
                                    </p>
                                    <p class="mt-1 text-foreground">
                                        {formatRunTime(evaluatedAt)}
                                    </p>
                                </div>
                                <div>
                                    <p
                                        class="text-xs uppercase text-muted-foreground"
                                    >
                                        Event
                                    </p>
                                    <p class="mt-1 text-foreground">
                                        {payload.type}
                                    </p>
                                </div>
                                <div>
                                    <p
                                        class="text-xs uppercase text-muted-foreground"
                                    >
                                        Visitor
                                    </p>
                                    <p class="mt-1 truncate text-foreground">
                                        {payload.visitor_id}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    {/if}
                </Card>

                {#if diagnostics}
                    {#if diagnostics.length === 0}
                        <Card>
                            <CardContent
                                class="py-6 text-sm text-muted-foreground"
                            >
                                No active segments are available for
                                diagnostics.
                            </CardContent>
                        </Card>
                    {:else}
                        {#each diagnostics as segment (segment.id)}
                            <Card
                                class={segment.matched
                                    ? 'border-green-200'
                                    : undefined}
                            >
                                <CardHeader>
                                    <div
                                        class="flex items-start justify-between gap-3"
                                    >
                                        <div>
                                            <CardTitle class="text-base">
                                                {segment.name}
                                            </CardTitle>
                                            <CardDescription>
                                                {segment.slug}
                                            </CardDescription>
                                        </div>
                                        <span
                                            class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium {segment.matched
                                                ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300'
                                                : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'}"
                                        >
                                            {#if segment.matched}
                                                <CircleCheck class="size-3.5" />
                                                Matched
                                            {:else}
                                                <CircleX class="size-3.5" />
                                                Not matched
                                            {/if}
                                        </span>
                                    </div>
                                </CardHeader>
                                <CardContent class="space-y-3">
                                    {#each segment.rules as rule (rule.id)}
                                        <div class="rounded-lg border p-3">
                                            <div
                                                class="flex items-start justify-between gap-3"
                                            >
                                                <div>
                                                    <p
                                                        class="text-sm font-medium"
                                                    >
                                                        {rule.type_label}
                                                    </p>
                                                    <p
                                                        class="text-xs text-muted-foreground"
                                                    >
                                                        Expected: {rule.expected}
                                                    </p>
                                                    <p
                                                        class="text-xs text-muted-foreground"
                                                    >
                                                        Actual: {formatActual(
                                                            rule.actual,
                                                        )}
                                                    </p>
                                                    {#if rule.note}
                                                        <p
                                                            class="mt-1 text-xs text-muted-foreground"
                                                        >
                                                            {rule.note}
                                                        </p>
                                                    {/if}
                                                </div>
                                                <span
                                                    class="rounded-full px-2 py-0.5 text-xs font-medium {rule.passed
                                                        ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300'
                                                        : 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300'}"
                                                >
                                                    {rule.passed
                                                        ? 'Passed'
                                                        : 'Failed'}
                                                </span>
                                            </div>
                                        </div>
                                    {/each}
                                </CardContent>
                            </Card>
                        {/each}
                    {/if}
                {/if}
            </div>
        </div>
    </div>

    <Dialog bind:open={saveDialogOpen}>
        <DialogContent>
            <DialogTitle>Save diagnostic scenario</DialogTitle>
            <DialogDescription>
                Save the current candidate event and latest result for future
                regression checks.
            </DialogDescription>
            <div class="space-y-2">
                <Label for="save-scenario-name">Scenario name</Label>
                <Input
                    id="save-scenario-name"
                    bind:value={scenarioName}
                    placeholder="Pricing visitor from Google - EN"
                />
                {#if scenarioError}
                    <p class="text-sm text-destructive">{scenarioError}</p>
                {/if}
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
                    type="button"
                    onclick={saveScenario}
                    disabled={isSavingScenario}
                >
                    <Save class="size-4" />
                    {isSavingScenario ? 'Saving' : 'Save scenario'}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</AppLayout>
