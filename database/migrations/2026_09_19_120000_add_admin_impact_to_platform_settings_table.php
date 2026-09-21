<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->boolean('admin_impact_enabled')->default(false)->after('email_recovery_enabled');
            $table->string('admin_impact_text')->nullable()->after('admin_impact_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->dropColumn(['admin_impact_enabled', 'admin_impact_text']);
        });
    }
};
