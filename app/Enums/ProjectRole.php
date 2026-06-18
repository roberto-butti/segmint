<?php

namespace App\Enums;

enum ProjectRole: string
{
    case Admin = 'admin';
    case Editor = 'editor';
    case Viewer = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Editor => 'Editor',
            self::Viewer => 'Viewer',
        };
    }

    public function canEditContent(): bool
    {
        return in_array($this, [self::Admin, self::Editor]);
    }

    public function canManageProject(): bool
    {
        return $this === self::Admin;
    }
}
