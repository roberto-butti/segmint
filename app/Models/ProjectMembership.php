<?php

namespace App\Models;

use App\Enums\ProjectRole;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ProjectMembership extends Pivot
{
    protected function casts(): array
    {
        return [
            'role' => ProjectRole::class,
        ];
    }
}
