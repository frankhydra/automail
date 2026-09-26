<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            // Matches the keys in config/plans.php. See PlanLimitService for how
            // this is used to check contacts/emails/team/sending-identity limits.
            $table->string('plan')->default('free')->after('slug');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('plan');
        });
    }
};
