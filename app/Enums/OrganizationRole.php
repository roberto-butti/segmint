<?php

namespace App\Enums;

enum OrganizationRole: string
{
    case Admin = 'admin';
    case Member = 'member';
    case Viewer = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Member => 'Member',
            self::Viewer => 'Viewer',
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
}
