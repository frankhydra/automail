<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A scheduled campaign has no one present to type a tag filter at the moment
     * it actually sends, so the filter chosen when scheduling must be saved and
     * replayed later by the scheduler.
     */
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->string('tag_filter')->nullable()->after('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn('tag_filter');
        });
    }
};
