<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->time('library_open_time')->default('09:00:00')->after('expiry_reminder_days');
            $table->time('library_close_time')->default('18:00:00')->after('library_open_time');
            $table->boolean('is_open_24_hours')->default(false)->after('library_close_time');
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn(['library_open_time', 'library_close_time', 'is_open_24_hours']);
        });
    }
};
