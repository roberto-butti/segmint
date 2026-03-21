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
            $table->jsonb('event_properties')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->jsonb('navigation_info')->nullable();
            $table->jsonb('utms')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_logs');
    }
};
