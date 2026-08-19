<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seat_bookings', function (Blueprint $table) {
            $table->timestamp('expiry_reminder_sent_at')->nullable()->after('cancellation_reason');
        });
    }

    public function down(): void
    {
        Schema::table('seat_bookings', function (Blueprint $table) {
            $table->dropColumn('expiry_reminder_sent_at');
        });
    }
};
