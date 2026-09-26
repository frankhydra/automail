<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Records which segment (if any) produced a campaign's audience snapshot,
     * for traceability. nullOnDelete: deleting a segment later must never
     * delete or break a campaign that already used it.
     */
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->foreignId('segment_id')->nullable()->after('template_id')->constrained('segments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropConstrainedForeignId('segment_id');
        });
    }
};
