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
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('public_id', 12)->nullable();
        });

        DB::table('organizations')
            ->select('id')
            ->orderBy('id')
            ->eachById(function (object $organization): void {
                DB::table('organizations')
                    ->where('id', $organization->id)
                    ->update(['public_id' => $this->generateUniquePublicId()]);
            });

        Schema::table('organizations', function (Blueprint $table) {
            $table->string('public_id', 12)->nullable(false)->change();
            $table->unique('public_id');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropUnique(['public_id']);
            $table->dropColumn('public_id');
        });
    }

    private function generateUniquePublicId(): string
    {
        do {
            $publicId = Str::random(12);
        } while (DB::table('organizations')->where('public_id', $publicId)->exists());

        return $publicId;
    }
};
