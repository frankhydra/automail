<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "blocks" is the visual builder's editable source (an ordered array of
     * {type, ...fields}); "body" stays the compiled, sendable HTML - the only
     * thing EmailDeliveryService/TemplateRendererService ever look at. A
     * template built with raw HTML instead of the builder simply has blocks = null.
     */
    public function up(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->json('blocks')->nullable()->after('body');
        });
    }

    public function down(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->dropColumn('blocks');
        });
    }
};
