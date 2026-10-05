<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Preview text" (the grey snippet shown next to the subject in an inbox).
     * It is compiled into the email body as a hidden preheader, so it travels
     * with the template into any campaign started from it.
     */
    public function up(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->string('preview_text')->nullable()->after('subject');
        });
    }

    public function down(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->dropColumn('preview_text');
        });
    }
};
