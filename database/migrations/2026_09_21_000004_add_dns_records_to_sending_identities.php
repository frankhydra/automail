<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sending_identities', function (Blueprint $table) {
            $table->string('domain')->nullable()->after('reply_to');
            $table->string('dkim_selector')->nullable()->after('domain');
            $table->text('dkim_public_key')->nullable()->after('dkim_selector');
            
            $table->string('spf_status')->default('unverified')->after('verification_status');
            $table->string('dkim_status')->default('unverified')->after('spf_status');
            $table->string('dmarc_status')->default('unverified')->after('dkim_status');
        });
    }

    public function down(): void
    {
        Schema::table('sending_identities', function (Blueprint $table) {
            $table->dropColumn([
                'domain', 
                'dkim_selector', 
                'dkim_public_key', 
                'spf_status', 
                'dkim_status', 
                'dmarc_status'
            ]);
        });
    }
};