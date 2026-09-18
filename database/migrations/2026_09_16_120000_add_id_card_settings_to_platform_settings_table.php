<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->string('id_card_template', 32)->default('classic')->after('favicon_path');
            $table->string('id_card_logo_path')->nullable()->after('id_card_template');
        });
    }

    public function down(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->dropColumn(['id_card_template', 'id_card_logo_path']);
        });
    }
};
