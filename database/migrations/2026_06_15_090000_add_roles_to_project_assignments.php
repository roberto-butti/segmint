<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_memberships', function (Blueprint $table) {
            $table->string('role')->default('viewer');
        });

        Schema::table('organization_invitation_projects', function (Blueprint $table) {
            $table->string('role')->default('viewer');
        });

        DB::table('organization_memberships')
            ->where('role', 'member')
            ->orderBy('id')
            ->each(function ($membership): void {
                $now = now();
                $assignments = DB::table('projects')
                    ->where('organization_id', $membership->organization_id)
                    ->pluck('id')
                    ->map(fn ($projectId) => [
                        'project_id' => $projectId,
                        'user_id' => $membership->user_id,
                        'role' => 'admin',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])
                    ->all();

                if ($assignments !== []) {
                    DB::table('project_memberships')->upsert(
                        $assignments,
                        ['project_id', 'user_id'],
                        ['role', 'updated_at'],
                    );
                }
            });
    }

    public function down(): void
    {
        Schema::table('organization_invitation_projects', function (Blueprint $table) {
            $table->dropColumn('role');
        });

        Schema::table('project_memberships', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
