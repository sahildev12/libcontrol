<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('platform_settings', 'email_finance_digest_enabled')) {
                $table->boolean('email_finance_digest_enabled')->default(true)->after('email_growth_enabled');
            }
        });
    }

    public function down(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            if (Schema::hasColumn('platform_settings', 'email_finance_digest_enabled')) {
                $table->dropColumn('email_finance_digest_enabled');
            }
        });
    }
};
