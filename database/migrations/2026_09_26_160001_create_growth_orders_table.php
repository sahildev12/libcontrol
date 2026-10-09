<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('growth_orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('order_type', 32); // package | service
            $table->string('item_key', 64);
            $table->string('item_name');
            $table->string('status', 32)->default('new'); // new, quoted, active, paused, completed, cancelled
            $table->string('priority', 16)->default('normal');
            $table->text('message')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 32)->nullable();
            $table->string('library_name')->nullable();
            $table->string('library_code', 64)->nullable();
            $table->string('deployment_domain')->nullable();
            $table->string('deployment_license_key_hash')->nullable();
            $table->unsignedBigInteger('support_ticket_id')->nullable();
            $table->string('razorpay_payment_id')->nullable();
            $table->string('razorpay_subscription_id')->nullable();
            $table->string('razorpay_payment_link_id')->nullable();
            $table->string('payment_status', 32)->nullable(); // pending, paid, failed
            $table->unsignedInteger('amount_paise')->nullable();
            $table->string('monthly_report_url')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamp('quoted_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('item_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('growth_orders');
    }
};
