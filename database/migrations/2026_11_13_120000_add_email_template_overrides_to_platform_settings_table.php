<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('platform_settings', 'email_template_overrides')) {
                $table->json('email_template_overrides')->nullable()->after('email_marketing_enabled');
            }
        });
    }

    public function down(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            if (Schema::hasColumn('platform_settings', 'email_template_overrides')) {
                $table->dropColumn('email_template_overrides');
            }
        });
    }
};
