<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create("segments", function (Blueprint $table) {
            $table->id();
            $table->foreignId("project_id")->constrained()->cascadeOnDelete();
            $table->string("name");
            $table->string("slug")->unique();
            $table->text("description")->nullable();
            $table->boolean("active")->default(true);
            $table->timestamps();
        });
        Schema::create("segment_rules", function (Blueprint $table) {
            $table->id();
            $table->foreignId("segment_id");
            $table->string("type"); // e.g. "comparison", "visit_count", "browser_language"
            $table->string("key"); // e.g. "utm_source"
            $table->string("operator"); // equals, contains, in, regex, etc.
            $table->string("value"); // e.g. "facebook"
            $table->integer("priority")->default(0); // useful for ordering rules
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("segment_rules");
        Schema::dropIfExists("segments");
    }
};
