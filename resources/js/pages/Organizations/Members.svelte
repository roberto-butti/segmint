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
    import type { BreadcrumbOrganization } from '@/lib/breadcrumbs';
    import { organizationBreadcrumbs } from '@/lib/breadcrumbs';
    import {
        isOrganizationRole,
        isProjectRole,
        OrganizationRole,
        type OrganizationRole as OrganizationRoleValue,
        type ProjectRole as ProjectRoleValue,
        requiresExplicitProjectAssignment,
    } from '@/lib/roles';
    import organizationInvitations from '@/routes/organizations/invitations';
    import organizationMembers from '@/routes/organizations/members';
    import type { BreadcrumbItem } from '@/types';

    interface Project {
        id: number;
        public_id: string;
        name: string;
        role?: ProjectRoleValue;
    }

    interface Member {
        id: number;
        name: string;
        email: string;
        role: OrganizationRoleValue | 'owner';
        projects: Project[];
        can_manage: boolean;
    }

    interface Invitation {
        id: number;
        public_id: string;
        email: string;
        role: OrganizationRoleValue;
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
        projectRoles,
    }: {
        organization: BreadcrumbOrganization;
        members: Member[];
        invitations: Invitation[];
        projects: Project[];
        roles: Role[];
        projectRoles: Role[];
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
    let inviteRole = $state<OrganizationRoleValue>(OrganizationRole.Member);
    let inviteProjects = $state<Record<number, ProjectRoleValue | null>>({});
    let processing = $state(false);
    let editingProjectsFor = $state<Member | null>(null);
    let projectSelection = $state<Record<number, ProjectRoleValue | null>>({});
    let projectDialogOpen = $state(false);

    function invite(): void {
        processing = true;
        router.post(
            organizationInvitations.store.url(organization.public_id),
            {
                email: inviteEmail,
                role: inviteRole,
                project_assignments: selectedAssignments(inviteProjects),
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    inviteOpen = false;
                    inviteEmail = '';
                    inviteRole = OrganizationRole.Member;
                    inviteProjects = Object.fromEntries(
                        projects.map((project) => [project.id, null]),
                    );
                },
                onFinish: () => (processing = false),
            },
        );
    }

    function updateRole(member: Member, role: OrganizationRoleValue): void {
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
                member.projects.find((assigned) => assigned.id === project.id)
                    ?.role ?? null,
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
            { assignments: selectedAssignments(projectSelection) },
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

    function selectedAssignments(
        selection: Record<number, ProjectRoleValue | null>,
    ): { project_id: number; role: ProjectRoleValue }[] {
        return Object.entries(selection).flatMap(([id, role]) =>
            role === null ? [] : [{ project_id: Number(id), role }],
        );
    }

    function setProjectRole(
        selection: Record<number, ProjectRoleValue | null>,
        projectId: number,
        role: string,
    ): void {
        selection[projectId] =
            role === 'unassigned'
                ? null
                : isProjectRole(role)
                  ? role
                  : selection[projectId];
    }
</script>

<AppHead title={`Members - ${organization.name}`} />

<AppLayout {breadcrumbs}>
    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold">Organization members</h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    Manage organization roles and explicit project access for
                    members and guests.
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
                        {#if requiresExplicitProjectAssignment(inviteRole)}
                            <div class="space-y-2">
                                <Label>Initial project roles</Label>
                                <p class="text-xs text-muted-foreground">
                                    Leave a project unassigned or choose the
                                    access level this user needs.
                                </p>
                                <div
                                    class="max-h-48 space-y-2 overflow-y-auto rounded-md border p-3"
                                >
                                    {#each projects as project (project.id)}
                                        <div
                                            class="flex items-center justify-between gap-3 text-sm"
                                        >
                                            <span>{project.name}</span>
                                            <Select
                                                type="single"
                                                value={inviteProjects[
                                                    project.id
                                                ] ?? 'unassigned'}
                                                onValueChange={(role) =>
                                                    setProjectRole(
                                                        inviteProjects,
                                                        project.id,
                                                        role,
                                                    )}
                                            >
                                                <SelectTrigger class="w-32"
                                                    >{projectRoles.find(
                                                        (role) =>
                                                            role.value ===
                                                            inviteProjects[
                                                                project.id
                                                            ],
                                                    )?.label ??
                                                        'Unassigned'}</SelectTrigger
                                                >
                                                <SelectContent>
                                                    <SelectItem
                                                        value="unassigned"
                                                        >Unassigned</SelectItem
                                                    >
                                                    {#each projectRoles as role (role.value)}
                                                        <SelectItem
                                                            value={role.value}
                                                            >{role.label}</SelectItem
                                                        >
                                                    {/each}
                                                </SelectContent>
                                            </Select>
                                        </div>
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
                            {#if member.role !== 'owner' && requiresExplicitProjectAssignment(member.role)}
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
                                    onValueChange={(role) => {
                                        if (isOrganizationRole(role)) {
                                            updateRole(member, role);
                                        }
                                    }}
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
                                {#if member.role !== 'owner' && requiresExplicitProjectAssignment(member.role)}
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
                                {#if requiresExplicitProjectAssignment(invitation.role)}
                                    <p
                                        class="mt-1 text-xs text-muted-foreground"
                                    >
                                        {invitation.projects.length} initial project
                                        {invitation.projects.length === 1
                                            ? 'assignment'
                                            : 'assignments'}
                                    </p>
                                {/if}
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
            >Members and guests can only access explicitly assigned projects.
            Choose a role for each required project.</DialogDescription
        >
        <div class="max-h-64 space-y-2 overflow-y-auto rounded-md border p-3">
            {#each projects as project (project.id)}
                <div class="flex items-center justify-between gap-3 text-sm">
                    <span>{project.name}</span>
                    <Select
                        type="single"
                        value={projectSelection[project.id] ?? 'unassigned'}
                        onValueChange={(role) =>
                            setProjectRole(projectSelection, project.id, role)}
                    >
                        <SelectTrigger class="w-32"
                            >{projectRoles.find(
                                (role) =>
                                    role.value === projectSelection[project.id],
                            )?.label ?? 'Unassigned'}</SelectTrigger
                        >
                        <SelectContent>
                            <SelectItem value="unassigned"
                                >Unassigned</SelectItem
                            >
                            {#each projectRoles as role (role.value)}
                                <SelectItem value={role.value}
                                    >{role.label}</SelectItem
                                >
                            {/each}
                        </SelectContent>
                    </Select>
                </div>
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
