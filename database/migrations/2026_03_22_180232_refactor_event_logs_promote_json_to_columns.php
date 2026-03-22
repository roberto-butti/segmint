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
        // Skip if columns already exist (fresh DB with updated base migration)
        if (Schema::hasColumn('event_logs', 'page_url')) {
            // Just drop old JSON columns if they still exist
            $dropColumns = collect(['navigation_info', 'utms'])
                ->filter(fn ($col) => Schema::hasColumn('event_logs', $col))
                ->all();

            if (! empty($dropColumns)) {
                Schema::table('event_logs', function (Blueprint $table) use ($dropColumns) {
                    $table->dropColumn($dropColumns);
                });
            }

            return;
        }

        Schema::table('event_logs', function (Blueprint $table) {
            $table->string('page_url')->nullable()->after('event_type');
            $table->string('page_path')->nullable()->after('page_url');
            $table->string('referrer_url')->nullable()->after('page_path');
            $table->string('utm_source')->nullable()->after('referrer_url');
            $table->string('utm_medium')->nullable()->after('utm_source');
            $table->string('utm_campaign')->nullable()->after('utm_medium');
            $table->string('utm_term')->nullable()->after('utm_campaign');
            $table->string('utm_content')->nullable()->after('utm_term');

            $table->index('page_path');
        });

        // Migrate existing data from JSON columns
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("
                UPDATE event_logs SET
                    page_url = navigation_info->>'page_url',
                    page_path = navigation_info->>'path',
                    referrer_url = navigation_info->>'referrer_url',
                    utm_source = utms->>'utm_source',
                    utm_medium = utms->>'utm_medium',
                    utm_campaign = utms->>'utm_campaign',
                    utm_term = utms->>'utm_term',
                    utm_content = utms->>'utm_content'
                WHERE navigation_info IS NOT NULL OR utms IS NOT NULL
            ");
        }

        Schema::table('event_logs', function (Blueprint $table) {
            $table->dropColumn(['navigation_info', 'utms']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_logs', function (Blueprint $table) {
            $table->jsonb('navigation_info')->nullable();
            $table->jsonb('utms')->nullable();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("
                UPDATE event_logs SET
                    navigation_info = jsonb_build_object(
                        'page_url', page_url,
                        'path', page_path,
                        'referrer_url', referrer_url
                    ),
                    utms = jsonb_build_object(
                        'utm_source', utm_source,
                        'utm_medium', utm_medium,
                        'utm_campaign', utm_campaign,
                        'utm_term', utm_term,
                        'utm_content', utm_content
                    )
            ");
        }

        Schema::table('event_logs', function (Blueprint $table) {
            $table->dropIndex(['page_path']);
            $table->dropColumn([
                'page_url', 'page_path', 'referrer_url',
                'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
            ]);
        });
    }
};
