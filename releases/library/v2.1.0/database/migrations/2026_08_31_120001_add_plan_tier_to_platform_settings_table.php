<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->string('plan_tier', 32)->default('starter')->after('student_code_padding');
            $table->unsignedInteger('max_seats_override')->nullable()->after('plan_tier');
            $table->unsignedInteger('max_halls_override')->nullable()->after('max_seats_override');
            $table->unsignedInteger('max_branches_override')->nullable()->after('max_halls_override');
        });
    }

    public function down(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->dropColumn([
                'plan_tier',
                'max_seats_override',
                'max_halls_override',
                'max_branches_override',
            ]);
        });
    }
};
