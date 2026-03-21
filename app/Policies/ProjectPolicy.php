<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    /**
     * Any organization member can view a project.
     */
    public function view(User $user, Project $project): bool
    {
        return $user->belongsToOrganization($project->organization);
    }

    /**
     * Members with manage permission can update a project.
     */
    public function update(User $user, Project $project): bool
    {
        $role = $user->roleInOrganization($project->organization);

        return $role !== null && $role->canManageProjects();
    }

    /**
     * Only owners and admins can delete a project.
     */
    public function delete(User $user, Project $project): bool
    {
        $role = $user->roleInOrganization($project->organization);

        return $role !== null && $role->canManageOrganization();
    }
}
