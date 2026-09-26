<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * provider_message_id lets an incoming webhook event ("this bounced") be
     * matched back to the exact recipient it happened to - it's the id the
     * provider handed back when we sent the email (see EmailDeliveryService).
     */
    public function up(): void
    {
        Schema::table('campaign_recipients', function (Blueprint $table) {
            $table->string('provider_message_id')->nullable()->after('sent_at')->index();
            $table->timestamp('delivered_at')->nullable()->after('provider_message_id');
            $table->timestamp('bounced_at')->nullable()->after('delivered_at');
            $table->timestamp('complained_at')->nullable()->after('bounced_at');
        });
    }

    public function down(): void
    {
        Schema::table('campaign_recipients', function (Blueprint $table) {
            $table->dropColumn(['provider_message_id', 'delivered_at', 'bounced_at', 'complained_at']);
        });
    }
};
