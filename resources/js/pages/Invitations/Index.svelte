<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import Mail from 'lucide-svelte/icons/mail';
    import AppHead from '@/components/AppHead.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import {
        Card,
        CardContent,
        CardHeader,
        CardTitle,
    } from '@/components/ui/card';
    import AppLayout from '@/layouts/AppLayout.svelte';
    import { dashboard } from '@/routes';
    import invitationsRoutes from '@/routes/invitations';
    import type { BreadcrumbItem } from '@/types';

    interface Invitation {
        id: number;
        public_id: string;
        organization: { public_id: string; name: string };
        invited_by: string;
        role: string;
        role_label: string;
        projects: { id: number; name: string }[];
        expires_at: string;
    }

    let { invitations }: { invitations: Invitation[] } = $props();
    let processing = $state<number | null>(null);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Organizations', href: dashboard() },
        { title: 'Invitations', href: invitationsRoutes.index() },
    ];

    function respond(
        invitation: Invitation,
        action: 'accept' | 'decline',
    ): void {
        processing = invitation.id;
        router.post(
            action === 'accept'
                ? invitationsRoutes.accept.url(invitation.public_id)
                : invitationsRoutes.decline.url(invitation.public_id),
            {},
            { onFinish: () => (processing = null) },
        );
    }
</script>

{#snippet invitationIcon()}<Mail class="size-8" />{/snippet}

<AppHead title="Invitations" />

<AppLayout {breadcrumbs}>
    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
        <div>
            <h2 class="text-xl font-semibold">Invitations</h2>
            <p class="mt-1 text-sm text-muted-foreground">
                Pending organization invitations sent to your email address.
            </p>
        </div>

        {#if invitations.length === 0}
            <EmptyState
                icon={invitationIcon}
                title="No pending invitations"
                description="New organization invitations will appear here."
            />
        {:else}
            <div class="grid gap-4 md:grid-cols-2">
                {#each invitations as invitation (invitation.id)}
                    <Card>
                        <CardHeader>
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <CardTitle class="text-base"
                                    >{invitation.organization.name}</CardTitle
                                >
                                <Badge variant="secondary"
                                    >{invitation.role_label}</Badge
                                >
                            </div>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <p class="text-sm text-muted-foreground">
                                Invited by {invitation.invited_by}. Expires {new Date(
                                    invitation.expires_at,
                                ).toLocaleDateString()}.
                            </p>
                            {#if invitation.role === 'guest'}
                                <p class="text-sm">
                                    Project access: {invitation.projects
                                        .length > 0
                                        ? invitation.projects
                                              .map((project) => project.name)
                                              .join(', ')
                                        : 'No projects assigned yet'}
                                </p>
                            {/if}
                            <div class="flex gap-2">
                                <Button
                                    size="sm"
                                    disabled={processing === invitation.id}
                                    onclick={() =>
                                        respond(invitation, 'accept')}
                                    >Accept</Button
                                >
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={processing === invitation.id}
                                    onclick={() =>
                                        respond(invitation, 'decline')}
                                    >Decline</Button
                                >
                            </div>
                        </CardContent>
                    </Card>
                {/each}
            </div>
        {/if}
    </div>
</AppLayout>
