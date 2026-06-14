<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('organization_memberships')
            ->where('role', 'guest')
            ->orderBy('id')
            ->each(function ($membership): void {
                $now = now();
                $assignments = DB::table('projects')
                    ->where('organization_id', $membership->organization_id)
                    ->pluck('id')
                    ->map(fn ($projectId) => [
                        'project_id' => $projectId,
                        'user_id' => $membership->user_id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])
                    ->all();

                if ($assignments !== []) {
                    DB::table('project_memberships')->insertOrIgnore($assignments);
                }
            });
    }

    public function down(): void
    {
        // Existing guest assignments cannot be distinguished from later explicit assignments.
    }
};
