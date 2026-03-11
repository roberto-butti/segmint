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
    }
};
