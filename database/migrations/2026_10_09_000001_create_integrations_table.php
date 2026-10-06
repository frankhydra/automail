<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per integration an organization has set up (type = webhook, utm, and
     * later shopify, stripe, ...). Settings are a small JSON blob. Secrets are never
     * stored in clear: only a SHA-256 hash of the intake token is kept.
     */
    public function up(): void
    {
        Schema::create('integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('type', 32);
            $table->boolean('enabled')->default(false);
            $table->json('settings')->nullable();
            $table->string('secret_hash', 64)->nullable()->unique();
            $table->unsignedBigInteger('uses')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integrations');
    }
};
