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
        Schema::create('event_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('session_id')->nullable();
            $table->string('uuid')->unique()->nullable();
            $table->string('visitor_id')->nullable();
            $table->string('event_type')->nullable();
            $table->string('page_url')->nullable();
            $table->string('page_path')->nullable()->index();
            $table->string('referrer_url')->nullable();
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_term')->nullable();
            $table->string('utm_content')->nullable();
            $table->jsonb('event_properties')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('segment_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_log_id')->constrained()->cascadeOnDelete();
            $table->foreignId('segment_id')->constrained()->cascadeOnDelete();
            $table->boolean('matched')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('segment_matches');
        Schema::dropIfExists('event_logs');
    }
};
