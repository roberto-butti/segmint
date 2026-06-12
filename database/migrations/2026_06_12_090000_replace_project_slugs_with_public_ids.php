<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('public_id', 12)->nullable();
        });

        DB::table('projects')
            ->select('id')
            ->orderBy('id')
            ->eachById(function (object $project): void {
                DB::table('projects')
                    ->where('id', $project->id)
                    ->update(['public_id' => $this->generateUniquePublicId()]);
            });

        Schema::table('projects', function (Blueprint $table) {
            $table->string('public_id', 12)->nullable(false)->change();
            $table->unique('public_id');
            $table->dropIndex(['slug']);
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('slug')->nullable()->index();
            $table->dropUnique(['public_id']);
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('public_id');
        });
    }

    private function generateUniquePublicId(): string
    {
        do {
            $publicId = Str::random(12);
        } while (DB::table('projects')->where('public_id', $publicId)->exists());

        return $publicId;
    }
};
