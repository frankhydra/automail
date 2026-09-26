<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The UI and controllers use "body" for template HTML, while the table used "content".
     * Rename it, and allow an empty default subject (the form marks it optional).
     */
    public function up(): void
    {
        if (Schema::hasColumn('templates', 'content') && !Schema::hasColumn('templates', 'body')) {
            Schema::table('templates', function (Blueprint $table) {
                $table->renameColumn('content', 'body');
            });
        }

        Schema::table('templates', function (Blueprint $table) {
            $table->string('subject')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('templates', 'body') && !Schema::hasColumn('templates', 'content')) {
            Schema::table('templates', function (Blueprint $table) {
                $table->renameColumn('body', 'content');
            });
        }
    }
};
