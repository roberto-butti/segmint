import { dashboard } from '@/routes';
import organizations from '@/routes/organizations';
import organizationProjects from '@/routes/organizations/projects';
import projects from '@/routes/projects';
import type { BreadcrumbItem } from '@/types';

export interface BreadcrumbOrganization {
    id: number;
    public_id: string;
    name: string;
    can_view_dashboard?: boolean;
}

export interface BreadcrumbProject {
    id: number;
    public_id: string;
    name: string;
}

export function organizationBreadcrumbs(
    organization: BreadcrumbOrganization,
): BreadcrumbItem[] {
    return [
        {
            title: 'Organizations',
            href: dashboard(),
        },
        {
            title: organization.name,
            href:
                organization.can_view_dashboard !== false
                    ? organizations.dashboard.url(organization.public_id)
                    : organizationProjects.index.url(organization.public_id),
        },
    ];
}

export function projectBreadcrumbs(
    organization: BreadcrumbOrganization,
    project: BreadcrumbProject,
): BreadcrumbItem[] {
    return [
        ...organizationBreadcrumbs(organization),
        {
            title: 'Projects',
            href: organizationProjects.index.url(organization.public_id),
        },
        {
            title: project.name,
            href: projects.show.url(project.public_id),
        },
    ];
}
