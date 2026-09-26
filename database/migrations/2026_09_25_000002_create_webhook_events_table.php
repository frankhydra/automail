<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A durable log of every provider webhook AutoMail receives - independent of
     * whether it could be matched to a recipient. This makes processing
     * idempotent (dedupe_key) and gives something concrete to check when a
     * bounce or complaint "should have" arrived but didn't visibly do anything.
     */
    public function up(): void
    {
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider'); // brevo, resend
            $table->string('event_type'); // delivered, bounced, complained, unsubscribed, ignored
            $table->string('dedupe_key')->unique();
            $table->foreignId('campaign_recipient_id')->nullable()->constrained('campaign_recipients')->nullOnDelete();
            $table->longText('payload');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
