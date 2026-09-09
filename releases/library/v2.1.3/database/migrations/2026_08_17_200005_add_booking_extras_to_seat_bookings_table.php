<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seat_bookings', function (Blueprint $table) {
            $table->time('custom_start_time')->nullable()->after('time_slot');
            $table->time('custom_end_time')->nullable()->after('custom_start_time');
            $table->string('membership_mode')->nullable()->after('custom_end_time');
        });
    }

    public function down(): void
    {
        Schema::table('seat_bookings', function (Blueprint $table) {
            $table->dropColumn(['custom_start_time', 'custom_end_time', 'membership_mode']);
        });
    }
};
