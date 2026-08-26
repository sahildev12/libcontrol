<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seat_booking_id')->constrained('seat_bookings')->cascadeOnDelete();
            $table->unsignedTinyInteger('installment_number');
            $table->decimal('amount', 10, 2);
            $table->date('due_date');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['seat_booking_id', 'installment_number']);
        });

        Schema::table('notification_reads', function (Blueprint $table) {
            $table->timestamp('dismissed_at')->nullable()->after('read_at');
        });
    }

    public function down(): void
    {
        Schema::table('notification_reads', function (Blueprint $table) {
            $table->dropColumn('dismissed_at');
        });

        Schema::dropIfExists('fee_installments');
    }
};
