<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->string('display_name')->nullable()->after('name');
            $table->string('student_code_prefix', 20)->nullable()->after('address');
            $table->unsignedTinyInteger('student_code_padding')->default(3)->after('student_code_prefix');
            $table->unsignedSmallInteger('expiry_reminder_days')->default(10)->after('student_code_padding');
            $table->string('logo_with_text_path')->nullable()->after('expiry_reminder_days');
            $table->string('simple_logo_path')->nullable()->after('logo_with_text_path');
            $table->string('favicon_path')->nullable()->after('simple_logo_path');
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn([
                'display_name',
                'student_code_prefix',
                'student_code_padding',
                'expiry_reminder_days',
                'logo_with_text_path',
                'simple_logo_path',
                'favicon_path',
            ]);
        });
    }
};
