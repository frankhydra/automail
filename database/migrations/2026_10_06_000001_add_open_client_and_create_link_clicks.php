<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Two small additions that power the Analytics page:
     *  - campaign_recipients.open_client: which mail app opened the email (best effort, see MailClientDetector)
     *  - link_clicks: one row per click, so "top performing links" can be counted per URL
     */
    public function up(): void
    {
        Schema::table('campaign_recipients', function (Blueprint $table) {
            $table->string('open_client', 32)->nullable()->after('opened_at');
        });

        Schema::create('link_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_recipient_id')->constrained('campaign_recipients')->cascadeOnDelete();
            $table->string('url', 2048);
            $table->timestamp('created_at')->nullable();

            $table->index('campaign_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('link_clicks');

        Schema::table('campaign_recipients', function (Blueprint $table) {
            $table->dropColumn('open_client');
        });
    }
};
