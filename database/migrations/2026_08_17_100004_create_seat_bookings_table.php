<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seat_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seat_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('time_slot');
            $table->string('fee_type');
            $table->decimal('fee_amount', 10, 2)->default(0);
            $table->date('joining_date');
            $table->date('plan_expiry_date');
            $table->string('status')->default('occupied');
            $table->date('trial_start')->nullable();
            $table->date('trial_end')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();

            $table->index(['seat_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seat_bookings');
    }
};
