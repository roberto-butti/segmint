export const OrganizationRole = {
    Admin: 'admin',
    Member: 'member',
    Guest: 'guest',
} as const;

export type OrganizationRole =
    (typeof OrganizationRole)[keyof typeof OrganizationRole];

export const ProjectRole = {
    Admin: 'admin',
    Editor: 'editor',
    Viewer: 'viewer',
} as const;

export type ProjectRole = (typeof ProjectRole)[keyof typeof ProjectRole];

export function isOrganizationRole(value: string): value is OrganizationRole {
    return Object.values(OrganizationRole).includes(value as OrganizationRole);
}

export function isProjectRole(value: string): value is ProjectRole {
    return Object.values(ProjectRole).includes(value as ProjectRole);
}

export function requiresExplicitProjectAssignment(
    role: OrganizationRole,
): boolean {
    return role === OrganizationRole.Member || role === OrganizationRole.Guest;
}
