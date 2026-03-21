<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Skip if organization_id already exists (e.g. fresh database with updated schema)
        if (Schema::hasColumn('projects', 'organization_id')) {
            // Just drop user_id if it still exists
            if (Schema::hasColumn('projects', 'user_id')) {
                Schema::table('projects', function (Blueprint $table) {
                    $table->dropForeign(['user_id']);
                    $table->dropColumn('user_id');
                });
            }

            return;
        }

        // Add nullable organization_id first
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('id');
        });

        // Create a personal organization for every user and migrate their projects
        User::all()->each(function (User $user): void {
            $orgId = DB::table('organizations')->insertGetId([
                'name' => $user->name."'s Organization",
                'slug' => Str::slug($user->name).'-'.Str::random(5),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('organization_memberships')->insert([
                'organization_id' => $orgId,
                'user_id' => $user->id,
                'role' => 'owner',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('projects')
                ->where('user_id', $user->id)
                ->update(['organization_id' => $orgId]);
        });

        // Make organization_id non-nullable and add foreign key constraint
        Schema::table('projects', function (Blueprint $table) {
            $table->unsignedBigInteger('organization_id')->nullable(false)->change();
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
        });

        // Drop user_id
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id');
        });

        // Restore user_id from organization owner memberships
        $memberships = DB::table('organization_memberships')
            ->where('role', 'owner')
            ->get();

        foreach ($memberships as $membership) {
            DB::table('projects')
                ->where('organization_id', $membership->organization_id)
                ->update(['user_id' => $membership->user_id]);
        }

        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->constrained()->cascadeOnDelete()->change();
            $table->dropForeign(['organization_id']);
            $table->dropColumn('organization_id');
        });
    }
};
