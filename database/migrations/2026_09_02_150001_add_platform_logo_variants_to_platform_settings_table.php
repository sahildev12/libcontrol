<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->string('simple_logo_path')->nullable()->after('logo_path');
            $table->string('logo_with_text_path')->nullable()->after('simple_logo_path');
        });

        DB::table('platform_settings')
            ->whereNotNull('logo_path')
            ->whereNull('logo_with_text_path')
            ->update(['logo_with_text_path' => DB::raw('logo_path')]);
    }

    public function down(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->dropColumn(['simple_logo_path', 'logo_with_text_path']);
        });
    }
};
