<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('event_logs', function (Blueprint $table) {
            $table->index(['project_id', 'created_at'], 'event_logs_project_created_at_idx');
            $table->index(['project_id', 'event_type', 'created_at'], 'event_logs_project_event_type_created_at_idx');
            $table->index(['project_id', 'visitor_id', 'event_type', 'page_path'], 'event_logs_project_visitor_type_path_idx');
            $table->index(['project_id', 'event_type', 'page_path', 'visitor_id'], 'event_logs_project_type_path_visitor_idx');
            $table->index(['project_id', 'utm_source'], 'event_logs_project_utm_source_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_logs', function (Blueprint $table) {
            $table->dropIndex('event_logs_project_created_at_idx');
            $table->dropIndex('event_logs_project_event_type_created_at_idx');
            $table->dropIndex('event_logs_project_visitor_type_path_idx');
            $table->dropIndex('event_logs_project_type_path_visitor_idx');
            $table->dropIndex('event_logs_project_utm_source_idx');
        });
    }
};
