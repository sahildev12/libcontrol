<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_automation_logs', function (Blueprint $table) {
            $table->id();
            $table->string('template_key', 80);
            $table->string('recipient_email');
            $table->string('related_type', 120)->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->unique(['template_key', 'related_type', 'related_id'], 'email_automation_unique_send');
            $table->index(['template_key', 'sent_at']);
        });

        Schema::table('platform_settings', function (Blueprint $table) {
            $table->boolean('email_fee_alerts_enabled')->default(true)->after('email_recovery_enabled');
            $table->boolean('email_seat_alerts_enabled')->default(true)->after('email_fee_alerts_enabled');
            $table->boolean('email_subscription_alerts_enabled')->default(true)->after('email_seat_alerts_enabled');
            $table->boolean('email_growth_enabled')->default(false)->after('email_subscription_alerts_enabled');
            $table->unsignedSmallInteger('seat_vacant_alert_threshold')->default(5)->after('email_growth_enabled');
            $table->unsignedSmallInteger('seat_high_occupancy_pct')->default(90)->after('seat_vacant_alert_threshold');
            $table->unsignedSmallInteger('growth_campaign_step')->default(0)->after('seat_high_occupancy_pct');
            $table->timestamp('subscription_trial_started_at')->nullable()->after('plan_paid_until');
            $table->timestamp('growth_last_sent_at')->nullable()->after('growth_campaign_step');
            $table->string('marketing_unsubscribe_token', 64)->nullable()->after('growth_last_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->dropColumn([
                'email_fee_alerts_enabled',
                'email_seat_alerts_enabled',
                'email_subscription_alerts_enabled',
                'email_growth_enabled',
                'seat_vacant_alert_threshold',
                'seat_high_occupancy_pct',
                'growth_campaign_step',
                'subscription_trial_started_at',
                'growth_last_sent_at',
                'marketing_unsubscribe_token',
            ]);
        });

        Schema::dropIfExists('email_automation_logs');
    }
};
