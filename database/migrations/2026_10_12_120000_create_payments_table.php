<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('merchant_order_id', 63)->unique();
                $table->string('gateway', 32)->default('phonepe');
                $table->string('purpose', 32); // software_plan | growth_package | growth_service
                $table->string('item_key', 64);
                $table->string('item_name');
                $table->string('billing_cycle', 16)->nullable(); // monthly | yearly
                $table->unsignedInteger('amount_paise');
                $table->string('status', 16)->default('created'); // created, pending, completed, failed
                $table->string('gateway_order_id')->nullable();
                $table->string('gateway_transaction_id')->nullable();
                $table->string('payment_mode', 32)->nullable();
                $table->string('error_code', 64)->nullable();
                $table->unsignedBigInteger('licensed_deployment_id')->nullable()->index();
                $table->string('deployment_domain')->nullable();
                $table->string('library_code', 64)->nullable();
                $table->string('library_name')->nullable();
                $table->uuid('growth_order_uuid')->nullable()->index();
                $table->string('payer_name')->nullable();
                $table->string('payer_email')->nullable();
                $table->string('payer_phone', 20)->nullable();
                $table->string('return_url', 500)->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamp('fulfilled_at')->nullable();
                $table->timestamp('last_checked_at')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->index(['status', 'created_at']);
            });
        }

        if (Schema::hasTable('licensed_deployments') && ! Schema::hasColumn('licensed_deployments', 'plan_paid_until')) {
            Schema::table('licensed_deployments', function (Blueprint $table) {
                $table->timestamp('plan_paid_until')->nullable();
            });
        }

        if (Schema::hasTable('platform_settings') && ! Schema::hasColumn('platform_settings', 'plan_paid_until')) {
            Schema::table('platform_settings', function (Blueprint $table) {
                $table->timestamp('plan_paid_until')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');

        if (Schema::hasColumn('licensed_deployments', 'plan_paid_until')) {
            Schema::table('licensed_deployments', fn (Blueprint $table) => $table->dropColumn('plan_paid_until'));
        }

        if (Schema::hasColumn('platform_settings', 'plan_paid_until')) {
            Schema::table('platform_settings', fn (Blueprint $table) => $table->dropColumn('plan_paid_until'));
        }
    }
};
