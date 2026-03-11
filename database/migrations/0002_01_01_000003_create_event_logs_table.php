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
            $table->string('uuid')->unique()->nullable(); // optional unique id (cookie-based)
            $table->string('visitor_id')->nullable();
            $table->string('event_type')->nullable(); // e.g., page_view, add_to_cart, recommendation_request
            $table->jsonb('event_properties')->nullable();

            /*
            ip_address	X-Forwarded-For, REMOTE_ADDR	use first public IP behind proxies
            user_agent	User-Agent header	raw string stored once
            accept_language	Accept-Language header	for locale detection
            host	Host header	useful for multi-domain setups
            request_method	GET/POST/etc	optional depending on tracking
             */
            $table->jsonb('metadata')->nullable();
            /*
            page_url	full URL of the event origin
            referrer_url	previous page or external source
            path	normalized URL path (/product/123)
            query_string (optional)	only if you need raw parameters
            */
            $table->jsonb('navigation_info')->nullable();
            /*
            utm_source	google, newsletter
            utm_medium	cpc, email
            utm_campaign	summer_sale
            utm_term	running shoes
            utm_content	banner_a
            */
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
