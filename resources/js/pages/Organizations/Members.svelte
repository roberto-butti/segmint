<script lang="ts">
    import { page, router } from '@inertiajs/svelte';
    import MailPlus from 'lucide-svelte/icons/mail-plus';
    import Trash2 from 'lucide-svelte/icons/trash-2';
    import AppHead from '@/components/AppHead.svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import {
        Card,
        CardContent,
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
    import { organizationBreadcrumbs } from '@/lib/breadcrumbs';
    import type { BreadcrumbOrganization } from '@/lib/breadcrumbs';
    import organizationInvitations from '@/routes/organizations/invitations';
    import organizationMembers from '@/routes/organizations/members';
    import type { BreadcrumbItem } from '@/types';

    interface Project {
        id: number;
        public_id: string;
        name: string;
    }

    interface Member {
        id: number;
        name: string;
        email: string;
        role: string;
        projects: Project[];
        can_manage: boolean;
    }

    interface Invitation {
        id: number;
        public_id: string;
        email: string;
        role: string;
        expires_at: string;
        invited_by: string;
        projects: Project[];
    }

    interface Role {
        value: string;
        label: string;
    }

    let {
        organization,
        members,
        invitations,
        projects,
        roles,
    }: {
        organization: BreadcrumbOrganization;
        members: Member[];
        invitations: Invitation[];
        projects: Project[];
        roles: Role[];
    } = $props();

    const breadcrumbs: BreadcrumbItem[] = $derived([
        ...organizationBreadcrumbs(organization),
        {
            title: 'Members',
            href: organizationMembers.index.url(organization.public_id),
        },
    ]);

    let inviteOpen = $state(false);
    let inviteEmail = $state('');
    let inviteRole = $state('member');
    let inviteProjects = $state<Record<number, boolean>>({});
    let processing = $state(false);
    let editingProjectsFor = $state<Member | null>(null);
    let projectSelection = $state<Record<number, boolean>>({});
    let projectDialogOpen = $state(false);

    function invite(): void {
        processing = true;
        router.post(
            organizationInvitations.store.url(organization.public_id),
            {
                email: inviteEmail,
                role: inviteRole,
                project_ids: selectedProjectIds(inviteProjects),
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    inviteOpen = false;
                    inviteEmail = '';
                    inviteRole = 'member';
                    inviteProjects = Object.fromEntries(
                        projects.map((project) => [project.id, false]),
                    );
                },
                onFinish: () => (processing = false),
            },
        );
    }

    function updateRole(member: Member, role: string): void {
        router.patch(
            organizationMembers.update.url({
                organization: organization.public_id,
                member: member.id,
            }),
            { role },
            { preserveScroll: true },
        );
    }

    function openProjectAssignments(member: Member): void {
        editingProjectsFor = member;
        projectDialogOpen = true;
        projectSelection = Object.fromEntries(
            projects.map((project) => [
                project.id,
                member.projects.some((assigned) => assigned.id === project.id),
            ]),
        );
    }

    function saveProjectAssignments(): void {
        if (!editingProjectsFor) {
            return;
        }

        processing = true;
        router.put(
            organizationMembers.projects.update.url({
                organization: organization.public_id,
                member: editingProjectsFor.id,
            }),
            { project_ids: selectedProjectIds(projectSelection) },
            {
                preserveScroll: true,
                onSuccess: () => {
                    projectDialogOpen = false;
                    editingProjectsFor = null;
                },
                onFinish: () => (processing = false),
            },
        );
    }

    function removeMember(member: Member): void {
        if (!confirm(`Remove ${member.name} from ${organization.name}?`)) {
            return;
        }

        router.delete(
            organizationMembers.destroy.url({
                organization: organization.public_id,
                member: member.id,
            }),
            { preserveScroll: true },
        );
    }

    function revokeInvitation(invitation: Invitation): void {
        router.delete(
            organizationInvitations.destroy.url({
                organization: organization.public_id,
                invitation: invitation.public_id,
            }),
            { preserveScroll: true },
        );
    }

    function selectedProjectIds(selection: Record<number, boolean>): number[] {
        return Object.entries(selection)
            .filter(([, selected]) => selected)
            .map(([id]) => Number(id));
    }
</script>

<AppHead title={`Members - ${organization.name}`} />

<AppLayout {breadcrumbs}>
    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold">Organization members</h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    Manage organization roles and project access for guests.
                </p>
            </div>
            <Dialog bind:open={inviteOpen}>
                <DialogTrigger asChild>
                    {#snippet children(props)}
                        <Button size="sm" onclick={props.onclick}>
                            <MailPlus class="size-4" />
                            Invite member
                        </Button>
                    {/snippet}
                </DialogTrigger>
                <DialogContent>
                    <DialogTitle>Invite member</DialogTitle>
                    <DialogDescription>
                        Invitations are sent by email and shown inside Segmint
                        for existing users.
                    </DialogDescription>
                    <form
                        class="space-y-4"
                        onsubmit={(event) => {
                            event.preventDefault();
                            invite();
                        }}
                    >
                        <div class="space-y-2">
                            <Label for="invite-email">Email</Label>
                            <Input
                                id="invite-email"
                                type="email"
                                bind:value={inviteEmail}
                                required
                            />
                            {#if page.props.errors.email}<p
                                    class="text-sm text-destructive"
                                >
                                    {page.props.errors.email}
                                </p>{/if}
                        </div>
                        <div class="space-y-2">
                            <Label>Role</Label>
                            <Select type="single" bind:value={inviteRole}>
                                <SelectTrigger class="w-full"
                                    >{roles.find(
                                        (role) => role.value === inviteRole,
                                    )?.label}</SelectTrigger
                                >
                                <SelectContent>
                                    {#each roles as role (role.value)}
                                        <SelectItem value={role.value}
                                            >{role.label}</SelectItem
                                        >
                                    {/each}
                                </SelectContent>
                            </Select>
                        </div>
                        {#if inviteRole === 'guest'}
                            <div class="space-y-2">
                                <Label>Initial project access</Label>
                                <div
                                    class="max-h-48 space-y-2 overflow-y-auto rounded-md border p-3"
                                >
                                    {#each projects as project (project.id)}
                                        <label
                                            class="flex items-center gap-2 text-sm"
                                        >
                                            <Checkbox
                                                checked={inviteProjects[
                                                    project.id
                                                ] ?? false}
                                                onclick={() =>
                                                    (inviteProjects[
                                                        project.id
                                                    ] = !(
                                                        inviteProjects[
                                                            project.id
                                                        ] ?? false
                                                    ))}
                                            />
                                            {project.name}
                                        </label>
                                    {/each}
                                </div>
                            </div>
                        {/if}
                        <DialogFooter>
                            <DialogClose asChild
                                >{#snippet children(props)}<Button
                                        variant="outline"
                                        type="button"
                                        onclick={props.onclick}>Cancel</Button
                                    >{/snippet}</DialogClose
                            >
                            <Button type="submit" disabled={processing}
                                >{processing
                                    ? 'Sending...'
                                    : 'Send invitation'}</Button
                            >
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>

        <Card>
            <CardHeader
                ><CardTitle class="text-base">Members</CardTitle></CardHeader
            >
            <CardContent class="space-y-3">
                {#each members as member (member.id)}
                    <div
                        class="flex flex-col gap-3 rounded-md border p-3 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="min-w-0">
                            <p class="truncate font-medium">{member.name}</p>
                            <p class="truncate text-sm text-muted-foreground">
                                {member.email}
                            </p>
                            {#if member.role === 'guest'}
                                <p class="mt-1 text-xs text-muted-foreground">
                                    {member.projects.length} assigned {member
                                        .projects.length === 1
                                        ? 'project'
                                        : 'projects'}
                                </p>
                            {/if}
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            {#if member.can_manage}
                                <Select
                                    type="single"
                                    value={member.role}
                                    onValueChange={(role) =>
                                        updateRole(member, role)}
                                >
                                    <SelectTrigger class="w-32"
                                        >{roles.find(
                                            (role) =>
                                                role.value === member.role,
                                        )?.label ?? member.role}</SelectTrigger
                                    >
                                    <SelectContent>
                                        {#each roles as role (role.value)}<SelectItem
                                                value={role.value}
                                                >{role.label}</SelectItem
                                            >{/each}
                                    </SelectContent>
                                </Select>
                                {#if member.role === 'guest'}
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onclick={() =>
                                            openProjectAssignments(member)}
                                        >Projects</Button
                                    >
                                {/if}
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    aria-label={`Remove ${member.name}`}
                                    onclick={() => removeMember(member)}
                                    ><Trash2 class="size-4" /></Button
                                >
                            {:else}
                                <Badge variant="secondary">{member.role}</Badge>
                            {/if}
                        </div>
                    </div>
                {/each}
            </CardContent>
        </Card>

        <Card>
            <CardHeader
                ><CardTitle class="text-base">Pending invitations</CardTitle
                ></CardHeader
            >
            <CardContent class="space-y-3">
                {#if invitations.length === 0}
                    <p class="text-sm text-muted-foreground">
                        No pending invitations.
                    </p>
                {:else}
                    {#each invitations as invitation (invitation.id)}
                        <div
                            class="flex items-center justify-between gap-3 rounded-md border p-3"
                        >
                            <div>
                                <p class="font-medium">{invitation.email}</p>
                                <p class="text-sm text-muted-foreground">
                                    {invitation.role} · invited by {invitation.invited_by}
                                </p>
                            </div>
                            <Button
                                variant="outline"
                                size="sm"
                                onclick={() => revokeInvitation(invitation)}
                                >Revoke</Button
                            >
                        </div>
                    {/each}
                {/if}
            </CardContent>
        </Card>
    </div>
</AppLayout>

<Dialog bind:open={projectDialogOpen}>
    <DialogContent>
        <DialogTitle>Project access for {editingProjectsFor?.name}</DialogTitle>
        <DialogDescription
            >Guests can only access explicitly assigned projects.</DialogDescription
        >
        <div class="max-h-64 space-y-2 overflow-y-auto rounded-md border p-3">
            {#each projects as project (project.id)}
                <label class="flex items-center gap-2 text-sm">
                    <Checkbox
                        checked={projectSelection[project.id] ?? false}
                        onclick={() =>
                            (projectSelection[project.id] = !(
                                projectSelection[project.id] ?? false
                            ))}
                    />
                    {project.name}
                </label>
            {/each}
        </div>
        <DialogFooter>
            <Button
                variant="outline"
                onclick={() => {
                    projectDialogOpen = false;
                    editingProjectsFor = null;
                }}>Cancel</Button
            >
            <Button disabled={processing} onclick={saveProjectAssignments}
                >Save access</Button
            >
        </DialogFooter>
    </DialogContent>
</Dialog>
