<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seat_bookings', function (Blueprint $table) {
            $table->string('payment_plan')->default('full')->after('fee_type');
            $table->string('installment_frequency')->nullable()->after('payment_plan');
            $table->timestamp('fee_paid_at')->nullable()->after('fee_amount');
        });

        DB::table('seat_bookings')->where('fee_type', 'installment')->update([
            'payment_plan' => 'installments',
            'fee_type' => 'monthly',
            'installment_frequency' => 'monthly',
        ]);

        DB::table('seat_bookings')->where('fee_type', 'membership')->update([
            'fee_type' => 'monthly',
        ]);

        DB::table('seat_bookings')->whereNull('fee_paid_at')->update([
            'fee_paid_at' => DB::raw('created_at'),
        ]);
    }

    public function down(): void
    {
        Schema::table('seat_bookings', function (Blueprint $table) {
            $table->dropColumn(['payment_plan', 'installment_frequency', 'fee_paid_at']);
        });
    }
};
