<?php

namespace App\Models;

use App\Enums\OrganizationRole;
use Illuminate\Database\Eloquent\Relations\Pivot;

class OrganizationMembership extends Pivot
{
    protected function casts(): array
    {
        return [
            'role' => OrganizationRole::class,
        ];
    }
}
