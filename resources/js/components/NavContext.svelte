<script lang="ts">
    import { Link, page, router } from '@inertiajs/svelte';
    import Activity from 'lucide-svelte/icons/activity';
    import Building2 from 'lucide-svelte/icons/building-2';
    import FileStack from 'lucide-svelte/icons/files';
    import FolderKanban from 'lucide-svelte/icons/folder-kanban';
    import KeyRound from 'lucide-svelte/icons/key-round';
    import LayoutDashboard from 'lucide-svelte/icons/layout-dashboard';
    import Star from 'lucide-svelte/icons/star';
    import Target from 'lucide-svelte/icons/target';
    import Users from 'lucide-svelte/icons/users';

    import {
        Select,
        SelectContent,
        SelectItem,
        SelectTrigger,
    } from '@/components/ui/select';
    import {
        SidebarGroup,
        SidebarGroupLabel,
        SidebarMenu,
        SidebarMenuButton,
        SidebarMenuItem,
    } from '@/components/ui/sidebar';
    import { currentUrlState } from '@/lib/currentUrl';
    import organizations from '@/routes/organizations';
    import organizationMembers from '@/routes/organizations/members';
    import organizationProjects from '@/routes/organizations/projects';
    import projects from '@/routes/projects';
    import accessTokens from '@/routes/projects/access-tokens';
    import events from '@/routes/projects/events';
    import ruleTemplates from '@/routes/projects/rule-templates';
    import segments from '@/routes/projects/segments';
    import type { NavItem } from '@/types';

    const context = $derived(page.props.navigationContext);
    const { currentUrl, isCurrentUrl } = currentUrlState();

    const organizationItems: NavItem[] = $derived(
        context.organization
            ? [
                  ...(context.canViewOrganizationDashboard
                      ? [
                            {
                                title: 'Dashboard',
                                href: organizations.dashboard.url(
                                    context.organization.public_id,
                                ),
                                icon: LayoutDashboard,
                            },
                        ]
                      : []),
                  {
                      title: 'Projects',
                      href: organizationProjects.index.url(
                          context.organization.public_id,
                      ),
                      icon: FolderKanban,
                  },
                  ...(context.canManageOrganization
                      ? [
                            {
                                title: 'Members',
                                href: organizationMembers.index.url(
                                    context.organization.public_id,
                                ),
                                icon: Users,
                            },
                        ]
                      : []),
              ]
            : [],
    );

    const projectItems: NavItem[] = $derived(
        context.project
            ? [
                  {
                      title: 'Overview',
                      href: projects.show.url(context.project.public_id),
                      icon: LayoutDashboard,
                  },
                  {
                      title: 'Segments',
                      href: segments.index.url(context.project.public_id),
                      icon: Target,
                  },
                  {
                      title: 'Events',
                      href: events.index.url(context.project.public_id),
                      icon: Activity,
                  },
                  {
                      title: 'Rule templates',
                      href: ruleTemplates.index.url(context.project.public_id),
                      icon: FileStack,
                  },
                  {
                      title: 'Access tokens',
                      href: accessTokens.index.url(context.project.public_id),
                      icon: KeyRound,
                  },
              ]
            : [],
    );

    function selectOrganization(publicId: string | undefined): void {
        if (publicId) {
            router.get(organizations.dashboard.url(publicId));
        }
    }

    function selectProject(publicId: string | undefined): void {
        if (publicId) {
            router.get(projects.show.url(publicId));
        }
    }
</script>

{#if context.organizations.length > 0 && !context.organization}
    <SidebarGroup class="group-data-[collapsible=icon]:hidden">
        <SidebarGroupLabel>Organization</SidebarGroupLabel>
        <Select type="single" value="" onValueChange={selectOrganization}>
            <SelectTrigger class="w-full">
                <div class="flex min-w-0 items-center gap-2">
                    <Building2 class="size-4 shrink-0 text-muted-foreground" />
                    <span class="truncate">Select organization</span>
                </div>
            </SelectTrigger>
            <SelectContent>
                {#each context.organizations as organization (organization.id)}
                    <SelectItem value={organization.public_id}>
                        {organization.name}
                    </SelectItem>
                {/each}
            </SelectContent>
        </Select>
    </SidebarGroup>
{/if}

{#if context.organization}
    <SidebarGroup class="border-t border-sidebar-border/70 px-2 pt-2 pb-0">
        <SidebarGroupLabel>Organization</SidebarGroupLabel>
        <p
            class="truncate px-2 pb-2 text-xs font-medium text-sidebar-foreground group-data-[collapsible=icon]:hidden"
            title={context.organization.name}
        >
            {context.organization.name}
        </p>
        <SidebarMenu>
            {#each organizationItems as item (item.title)}
                <SidebarMenuItem>
                    <SidebarMenuButton
                        asChild
                        isActive={isCurrentUrl(
                            item.href,
                            currentUrl(),
                            item.title !== 'Dashboard',
                        )}
                        tooltip={item.title}
                    >
                        {#snippet children(props)}
                            <Link
                                {...props}
                                href={item.href}
                                class={props.class}
                            >
                                <item.icon class="size-4 shrink-0" />
                                <span>{item.title}</span>
                            </Link>
                        {/snippet}
                    </SidebarMenuButton>
                </SidebarMenuItem>
            {/each}
        </SidebarMenu>
    </SidebarGroup>

    {#if context.projects.length > 0 && !context.project}
        <SidebarGroup
            class="border-t border-sidebar-border/70 group-data-[collapsible=icon]:hidden"
        >
            <SidebarGroupLabel>Project</SidebarGroupLabel>
            <Select type="single" value="" onValueChange={selectProject}>
                <SelectTrigger class="w-full">
                    <div class="flex min-w-0 items-center gap-2">
                        <FolderKanban
                            class="size-4 shrink-0 text-muted-foreground"
                        />
                        <span class="truncate">Select project</span>
                    </div>
                </SelectTrigger>
                <SelectContent>
                    {#each context.projects as project (project.id)}
                        <SelectItem value={project.public_id}>
                            <span class="flex min-w-0 items-center gap-2">
                                {#if project.is_favorite}
                                    <Star
                                        class="size-3.5 fill-amber-400 text-amber-500"
                                    />
                                {/if}
                                <span class="truncate">{project.name}</span>
                            </span>
                        </SelectItem>
                    {/each}
                </SelectContent>
            </Select>
        </SidebarGroup>
    {/if}
{/if}

{#if context.project}
    <SidebarGroup class="border-t border-sidebar-border/70 px-2 pt-2 pb-0">
        <SidebarGroupLabel>Project</SidebarGroupLabel>
        <p
            class="truncate px-2 pb-2 text-xs font-medium text-sidebar-foreground group-data-[collapsible=icon]:hidden"
            title={context.project.name}
        >
            {context.project.name}
        </p>
        <SidebarMenu>
            {#each projectItems as item (item.title)}
                <SidebarMenuItem>
                    <SidebarMenuButton
                        asChild
                        isActive={isCurrentUrl(
                            item.href,
                            currentUrl(),
                            item.title !== 'Overview',
                        )}
                        tooltip={item.title}
                    >
                        {#snippet children(props)}
                            <Link
                                {...props}
                                href={item.href}
                                class={props.class}
                            >
                                <item.icon class="size-4 shrink-0" />
                                <span>{item.title}</span>
                            </Link>
                        {/snippet}
                    </SidebarMenuButton>
                </SidebarMenuItem>
            {/each}
        </SidebarMenu>
    </SidebarGroup>
{/if}
