<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import ShieldCheck from 'lucide-svelte/icons/shield-check';
    import Trash2 from 'lucide-svelte/icons/trash-2';

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
    import {
        Select,
        SelectContent,
        SelectItem,
        SelectTrigger,
    } from '@/components/ui/select';
    import AppLayout from '@/layouts/AppLayout.svelte';
    import type { BreadcrumbOrganization } from '@/lib/breadcrumbs';
    import { projectBreadcrumbs } from '@/lib/breadcrumbs';
    import {
        isProjectRole,
        type OrganizationRole,
        type ProjectRole,
    } from '@/lib/roles';
    import projectMembers from '@/routes/projects/members';
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
        organization_role: OrganizationRole | 'owner';
        project_role: ProjectRole | null;
        implicit: boolean;
    }

    interface Role {
        value: string;
        label: string;
    }

    let {
        project,
        organization,
        members,
        projectRoles,
    }: {
        project: Project;
        organization: BreadcrumbOrganization;
        members: Member[];
        projectRoles: Role[];
    } = $props();

    const breadcrumbs: BreadcrumbItem[] = $derived([
        ...projectBreadcrumbs(organization, project),
        {
            title: 'Members',
            href: projectMembers.index.url(project.public_id),
        },
    ]);

    function updateRole(member: Member, role: ProjectRole): void {
        router.put(
            projectMembers.update.url({
                project: project.public_id,
                member: member.id,
            }),
            { role },
            { preserveScroll: true },
        );
    }

    function removeAccess(member: Member): void {
        if (!confirm(`Remove ${member.name}'s access to ${project.name}?`)) {
            return;
        }

        router.delete(
            projectMembers.destroy.url({
                project: project.public_id,
                member: member.id,
            }),
            { preserveScroll: true },
        );
    }
</script>

{#snippet emptyIcon()}<ShieldCheck class="size-8" />{/snippet}

<AppHead title={`Members - ${project.name}`} />

<AppLayout {breadcrumbs}>
    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
        <div>
            <h2 class="text-xl font-semibold">Project members</h2>
            <p class="mt-1 text-sm text-muted-foreground">
                Organization owners and admins always have project-admin access.
                Assign members and guests as project admins, editors, or
                viewers.
            </p>
        </div>

        <Card>
            <CardHeader>
                <CardTitle class="text-base">Organization users</CardTitle>
            </CardHeader>
            <CardContent class="space-y-3">
                {#if members.length === 0}
                    <EmptyState
                        icon={emptyIcon}
                        title="No organization users"
                        description="Invite users from the organization members page first."
                    />
                {:else}
                    {#each members as member (member.id)}
                        <div
                            class="flex flex-col gap-3 rounded-md border p-3 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div class="min-w-0">
                                <p class="truncate font-medium">
                                    {member.name}
                                </p>
                                <p
                                    class="truncate text-sm text-muted-foreground"
                                >
                                    {member.email} · {member.organization_role}
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                {#if member.implicit}
                                    <Badge variant="secondary"
                                        >Project admin · implicit</Badge
                                    >
                                {:else}
                                    <Select
                                        type="single"
                                        value={member.project_role ?? undefined}
                                        onValueChange={(role) => {
                                            if (isProjectRole(role)) {
                                                updateRole(member, role);
                                            }
                                        }}
                                    >
                                        <SelectTrigger class="w-36"
                                            >{projectRoles.find(
                                                (role) =>
                                                    role.value ===
                                                    member.project_role,
                                            )?.label ??
                                                'Assign role'}</SelectTrigger
                                        >
                                        <SelectContent>
                                            {#each projectRoles as role (role.value)}
                                                <SelectItem value={role.value}
                                                    >{role.label}</SelectItem
                                                >
                                            {/each}
                                        </SelectContent>
                                    </Select>
                                    {#if member.project_role}
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            aria-label={`Remove ${member.name}'s project access`}
                                            onclick={() => removeAccess(member)}
                                        >
                                            <Trash2 class="size-4" />
                                        </Button>
                                    {/if}
                                {/if}
                            </div>
                        </div>
                    {/each}
                {/if}
            </CardContent>
        </Card>
    </div>
</AppLayout>
