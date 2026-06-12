import type { LinkComponentBaseProps } from '@inertiajs/core';
import type { Component, SvelteComponent } from 'svelte';

type NavIcon =
    | Component<{ class?: string }>
    | (new (...args: any[]) => SvelteComponent<{ class?: string }>);

export type BreadcrumbItem = {
    title: string;
    href: NonNullable<LinkComponentBaseProps['href']>;
    variant?: 'default' | 'action';
};

export type NavItem = {
    title: string;
    href: NonNullable<LinkComponentBaseProps['href']>;
    icon?: NavIcon;
    isActive?: boolean;
};

export type NavigationContextItem = {
    id: number;
    public_id: string;
    name: string;
};

export type NavigationContext = {
    organizations: NavigationContextItem[];
    organization: NavigationContextItem | null;
    projects: NavigationContextItem[];
    project: NavigationContextItem | null;
};
