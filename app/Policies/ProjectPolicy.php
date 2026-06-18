<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    /**
     * Any resolved project role can view a project.
     */
    public function view(User $user, Project $project): bool
    {
        return $user->roleInProject($project) !== null;
    }

    /**
     * Project admins and editors can update project content.
     */
    public function update(User $user, Project $project): bool
    {
        return $user->roleInProject($project)?->canEditContent() === true;
    }

    /**
     * Project admins can manage settings, tokens, and assignments.
     */
    public function manage(User $user, Project $project): bool
    {
        return $user->roleInProject($project)?->canManageProject() === true;
    }

    /**
     * Only owners and admins can delete a project.
     */
    public function delete(User $user, Project $project): bool
    {
        $role = $user->roleInOrganization($project->organization);

        return $user->isOwnerOf($project->organization)
            || ($role !== null && $role->canManageOrganization());
    }
}
