<?php

namespace App\Enums;

enum OrganizationRole: string
{
    case Admin = 'admin';
    case Member = 'member';
    case Guest = 'guest';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Member => 'Member',
            self::Guest => 'Guest',
        };
    }

    public function canManageProjects(): bool
    {
        return in_array($this, [self::Admin, self::Member]);
    }

    public function canManageOrganization(): bool
    {
        return $this === self::Admin;
    }

    public function canAccessAllProjects(): bool
    {
        return $this !== self::Guest;
    }
}
