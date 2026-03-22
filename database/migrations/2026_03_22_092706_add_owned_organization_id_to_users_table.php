<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Skip if column already exists (added in base migration for fresh DBs)
        if (! Schema::hasColumn('users', 'owned_organization_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('owned_organization_id')
                    ->nullable()
                    ->unique()
                    ->after('remember_token')
                    ->constrained('organizations')
                    ->nullOnDelete();
            });
        }

        // Migrate existing owner memberships to the new column
        $owners = DB::table('organization_memberships')
            ->where('role', 'owner')
            ->get();

        foreach ($owners as $membership) {
            DB::table('users')
                ->where('id', $membership->user_id)
                ->update(['owned_organization_id' => $membership->organization_id]);

            // Change pivot role from owner to admin
            DB::table('organization_memberships')
                ->where('id', $membership->id)
                ->update(['role' => 'admin']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore owner role in pivot from owned_organization_id
        $users = DB::table('users')
            ->whereNotNull('owned_organization_id')
            ->get();

        foreach ($users as $user) {
            DB::table('organization_memberships')
                ->where('user_id', $user->id)
                ->where('organization_id', $user->owned_organization_id)
                ->update(['role' => 'owner']);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['owned_organization_id']);
            $table->dropColumn('owned_organization_id');
        });
    }
};
