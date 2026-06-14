<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;

abstract class Controller
{
    use AuthorizesRequests;

    /**
     * @return array{id: int, public_id: string, name: string, can_view_dashboard: bool}
     */
    protected function organizationContext(Organization $organization): array
    {
        return [
            'id' => $organization->id,
            'public_id' => $organization->public_id,
            'name' => $organization->name,
            'can_view_dashboard' => Auth::user()?->belongsToOrganization($organization) === true,
        ];
    }
}
