<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Marketing automation (spec Phase 3.1): Trigger -> Condition -> Delay -> Action.
     *
     *  automations        the journey itself: its trigger and sender
     *  automation_nodes   the steps. A tree: each node points at its parent, and a
     *                     condition's two children carry branch = yes / no
     *  automation_runs    one row per contact travelling through a journey
     *  automation_events  a plain-language log of what happened to each run
     */
    public function up(): void
    {
        Schema::create('automations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sending_identity_id')->nullable()->constrained('sending_identities')->nullOnDelete();
            $table->string('name');
            $table->string('status', 16)->default('draft'); // draft, active, paused
            $table->string('trigger_type', 32);              // contact_added, tag_added
            $table->json('trigger_config')->nullable();      // e.g. {"tag": "vip"}
            $table->timestamps();

            $table->index(['organization_id', 'status']);
        });

        Schema::create('automation_nodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('automation_nodes')->cascadeOnDelete();
            $table->string('branch', 8)->nullable();         // null = "next", or yes / no under a condition
            $table->string('type', 16);                      // email, wait, condition
            $table->json('config');
            $table->timestamps();

            $table->index(['automation_id', 'parent_id']);
        });

        Schema::create('automation_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->string('status', 16)->default('active'); // active, completed, cancelled, failed
            $table->foreignId('current_node_id')->nullable()->constrained('automation_nodes')->nullOnDelete();
            $table->unsignedBigInteger('last_recipient_id')->nullable(); // the most recent email sent in this run
            $table->timestamp('next_run_at')->nullable();
            $table->timestamp('claimed_at')->nullable();      // set while a worker is processing the run
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // A contact goes through a given journey once.
            $table->unique(['automation_id', 'contact_id']);
            $table->index(['status', 'next_run_at']);
        });

        Schema::create('automation_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_run_id')->constrained('automation_runs')->cascadeOnDelete();
            $table->unsignedBigInteger('automation_node_id')->nullable();
            $table->string('event', 32);
            $table->string('detail', 500)->nullable();
            $table->timestamp('created_at')->nullable();
        });

        // Each email step sends through a hidden "system" campaign, so tracking, the
        // unsubscribe link, analytics and plan limits all work exactly as for a normal
        // campaign. They carry status = 'automation' and are kept out of campaign lists.
        Schema::table('campaigns', function (Blueprint $table) {
            $table->unsignedBigInteger('automation_id')->nullable()->after('segment_id');
            $table->unsignedBigInteger('automation_node_id')->nullable()->after('automation_id');
            $table->index(['automation_id', 'automation_node_id']);
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropIndex(['automation_id', 'automation_node_id']);
            $table->dropColumn(['automation_id', 'automation_node_id']);
        });

        Schema::dropIfExists('automation_events');
        Schema::dropIfExists('automation_runs');
        Schema::dropIfExists('automation_nodes');
        Schema::dropIfExists('automations');
    }
};
