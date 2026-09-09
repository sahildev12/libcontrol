<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('app_pin_hash')->nullable()->after('is_family_primary');
            $table->timestamp('app_pin_set_at')->nullable()->after('app_pin_hash');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['app_pin_hash', 'app_pin_set_at']);
        });
    }
};
