<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('licensed_deployments', function (Blueprint $table) {
            $table->string('plan_tier')->default('starter')->after('notes');
            $table->unsignedInteger('max_seats_override')->nullable()->after('plan_tier');
            $table->unsignedInteger('max_halls_override')->nullable()->after('max_seats_override');
            $table->unsignedInteger('max_branches_override')->nullable()->after('max_halls_override');
        });

        Schema::create('deployment_commands', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('licensed_deployment_id')->constrained('licensed_deployments')->cascadeOnDelete();
            $table->string('action');
            $table->json('payload')->nullable();
            $table->string('status')->default('pending');
            $table->string('idempotency_key', 64)->nullable();
            $table->text('result')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['licensed_deployment_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deployment_commands');

        Schema::table('licensed_deployments', function (Blueprint $table) {
            $table->dropColumn([
                'plan_tier',
                'max_seats_override',
                'max_halls_override',
                'max_branches_override',
            ]);
        });
    }
};
