<script lang="ts">
    import { page, router } from '@inertiajs/svelte';
    import Check from 'lucide-svelte/icons/check';
    import Copy from 'lucide-svelte/icons/copy';
    import KeyRound from 'lucide-svelte/icons/key-round';
    import RotateCw from 'lucide-svelte/icons/rotate-cw';
    import X from 'lucide-svelte/icons/x';

    import AppHead from '@/components/AppHead.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
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
    import AppLayout from '@/layouts/AppLayout.svelte';
    import type { BreadcrumbOrganization } from '@/lib/breadcrumbs';
    import { projectBreadcrumbs } from '@/lib/breadcrumbs';
    import accessTokens from '@/routes/projects/access-tokens';
    import type { BreadcrumbItem } from '@/types';

    interface Project {
        id: number;
        name: string;
        public_id: string;
    }

    interface AccessToken {
        id: number;
        name: string | null;
        preview: string;
        active: boolean;
        last_used_at: string | null;
        created_at: string;
    }

    let {
        project,
        organization,
        accessTokens: tokenList,
        canManageProject,
    }: {
        project: Project;
        organization: BreadcrumbOrganization;
        accessTokens: AccessToken[];
        canManageProject: boolean;
    } = $props();

    const breadcrumbs: BreadcrumbItem[] = $derived([
        ...projectBreadcrumbs(organization, project),
        {
            title: 'Access Tokens',
            href: accessTokens.index.url(project.public_id),
        },
    ]);

    const accessTokenSecret = $derived(page.props.flash.accessTokenSecret);
    let dismissedSecretId = $state<string | null>(null);
    let copied = $state(false);
    let createOpen = $state(false);
    let createName = $state('');
    let createProcessing = $state(false);
    let actionProcessing = $state<number | null>(null);
    let rotateToken = $state<AccessToken | null>(null);
    let rotateOpen = $state(false);

    function createToken(): void {
        createProcessing = true;
        router.post(
            accessTokens.store.url(project.public_id),
            { name: createName },
            {
                preserveScroll: true,
                onSuccess: () => {
                    createOpen = false;
                    createName = '';
                    copied = false;
                },
                onFinish: () => {
                    createProcessing = false;
                },
            },
        );
    }

    function setActive(token: AccessToken, active: boolean): void {
        actionProcessing = token.id;
        router.patch(
            accessTokens.update.url({
                project: project.public_id,
                accessToken: token.id,
            }),
            { active },
            {
                preserveScroll: true,
                onFinish: () => {
                    actionProcessing = null;
                },
            },
        );
    }

    function rotate(): void {
        if (!rotateToken) {
            return;
        }

        actionProcessing = rotateToken.id;
        router.post(
            accessTokens.rotate.url({
                project: project.public_id,
                accessToken: rotateToken.id,
            }),
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    rotateOpen = false;
                    rotateToken = null;
                    copied = false;
                },
                onFinish: () => {
                    actionProcessing = null;
                },
            },
        );
    }

    async function copySecret(): Promise<void> {
        if (!accessTokenSecret) {
            return;
        }

        await navigator.clipboard.writeText(accessTokenSecret.token);
        copied = true;
    }
</script>

{#snippet tokenIcon()}
    <KeyRound class="size-8" />
{/snippet}

{#snippet createTokenAction()}
    <Button size="sm" onclick={() => (createOpen = true)}>
        Create access token
    </Button>
{/snippet}

<AppHead title={`Access Tokens - ${project.name}`} />

<AppLayout {breadcrumbs}>
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold">Access tokens</h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    Tokens identify this project when sending events or reading
                    active segments.
                </p>
            </div>
            {#if canManageProject}
                <Dialog bind:open={createOpen}>
                    <DialogTrigger asChild>
                        {#snippet children(props)}
                            <Button size="sm" onclick={props.onclick}>
                                Create access token
                            </Button>
                        {/snippet}
                    </DialogTrigger>
                    <DialogContent>
                        <DialogTitle>Create access token</DialogTitle>
                        <DialogDescription>
                            Give the token a name that identifies the
                            application or integration using it.
                        </DialogDescription>
                        <form
                            class="space-y-4"
                            onsubmit={(event) => {
                                event.preventDefault();
                                createToken();
                            }}
                        >
                            <div class="space-y-2">
                                <Label for="access-token-name">Name</Label>
                                <Input
                                    id="access-token-name"
                                    bind:value={createName}
                                    placeholder="e.g. Production website"
                                    required
                                    maxlength={255}
                                />
                                {#if page.props.errors.name}
                                    <p class="text-sm text-destructive">
                                        {page.props.errors.name}
                                    </p>
                                {/if}
                            </div>
                            <DialogFooter>
                                <DialogClose asChild>
                                    {#snippet children(props)}
                                        <Button
                                            variant="outline"
                                            type="button"
                                            onclick={props.onclick}
                                        >
                                            Cancel
                                        </Button>
                                    {/snippet}
                                </DialogClose>
                                <Button
                                    type="submit"
                                    disabled={createProcessing}
                                >
                                    {createProcessing
                                        ? 'Creating...'
                                        : 'Create token'}
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            {/if}
        </div>

        {#if accessTokenSecret && dismissedSecretId !== accessTokenSecret.id}
            <Alert class="border-amber-300 bg-amber-50 dark:bg-amber-950/30">
                <KeyRound />
                <AlertTitle>
                    {accessTokenSecret.action === 'created'
                        ? 'Access token created'
                        : 'Access token rotated'}
                </AlertTitle>
                <AlertDescription class="space-y-3">
                    <p>
                        Copy the token for
                        <strong>{accessTokenSecret.name}</strong> now. It will not
                        be shown again after you leave or refresh this page.
                    </p>
                    <div class="flex items-center gap-2">
                        <code
                            class="min-w-0 flex-1 overflow-x-auto rounded-md border bg-background px-3 py-2 font-mono text-xs"
                        >
                            {accessTokenSecret.token}
                        </code>
                        <Button
                            variant="outline"
                            size="sm"
                            onclick={copySecret}
                        >
                            {#if copied}
                                <Check class="size-4" />
                                Copied
                            {:else}
                                <Copy class="size-4" />
                                Copy
                            {/if}
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            aria-label="Dismiss access token"
                            onclick={() => {
                                dismissedSecretId = accessTokenSecret.id;
                                router.clearHistory();
                            }}
                        >
                            <X class="size-4" />
                        </Button>
                    </div>
                </AlertDescription>
            </Alert>
        {/if}

        {#if tokenList.length === 0}
            <EmptyState
                icon={tokenIcon}
                title={`No access tokens for ${project.name}`}
                description="Create an access token before sending events to this project."
                class="flex-1"
                actions={canManageProject ? createTokenAction : undefined}
            />
        {:else}
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                {#each tokenList as token (token.id)}
                    <Card>
                        <CardHeader>
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <CardTitle class="truncate text-base">
                                    {token.name ?? 'Unnamed token'}
                                </CardTitle>
                                <span
                                    class="inline-flex shrink-0 items-center rounded-full px-2 py-0.5 text-xs font-medium {token.active
                                        ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300'
                                        : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'}"
                                >
                                    {token.active ? 'Active' : 'Revoked'}
                                </span>
                            </div>
                        </CardHeader>
                        <CardContent class="space-y-1">
                            <p class="font-mono text-sm text-muted-foreground">
                                {token.preview}
                            </p>
                            {#if token.last_used_at}
                                <p class="text-xs text-muted-foreground">
                                    Last used: {new Date(
                                        token.last_used_at,
                                    ).toLocaleDateString()}
                                </p>
                            {:else}
                                <p class="text-xs text-muted-foreground">
                                    Never used
                                </p>
                            {/if}
                        </CardContent>
                        {#if canManageProject}
                            <CardFooter class="flex gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    class="flex-1"
                                    disabled={actionProcessing === token.id}
                                    onclick={() =>
                                        setActive(token, !token.active)}
                                >
                                    {token.active ? 'Revoke' : 'Reactivate'}
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    class="flex-1"
                                    disabled={actionProcessing === token.id}
                                    onclick={() => {
                                        rotateToken = token;
                                        rotateOpen = true;
                                    }}
                                >
                                    <RotateCw class="size-4" />
                                    Rotate
                                </Button>
                            </CardFooter>
                        {/if}
                    </Card>
                {/each}
            </div>
        {/if}
    </div>

    <Dialog bind:open={rotateOpen}>
        <DialogContent>
            <DialogTitle>Rotate access token</DialogTitle>
            <DialogDescription>
                Rotating <strong>{rotateToken?.name ?? 'this token'}</strong>
                immediately invalidates its current value. The replacement will be
                shown once and must be updated in every client using it. Revoked tokens
                remain revoked after rotation.
            </DialogDescription>
            <DialogFooter>
                <DialogClose asChild>
                    {#snippet children(props)}
                        <Button variant="outline" onclick={props.onclick}>
                            Cancel
                        </Button>
                    {/snippet}
                </DialogClose>
                <Button
                    variant="destructive"
                    onclick={rotate}
                    disabled={actionProcessing !== null}
                >
                    Rotate token
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</AppLayout>
