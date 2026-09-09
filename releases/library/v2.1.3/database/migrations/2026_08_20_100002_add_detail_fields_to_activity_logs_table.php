<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->string('actor_type', 20)->nullable()->after('user_id');
            $table->string('method', 10)->nullable()->after('ip_address');
            $table->string('url', 500)->nullable()->after('method');
            $table->text('user_agent')->nullable()->after('url');
            $table->index('actor_type');
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex(['actor_type']);
            $table->dropColumn(['actor_type', 'method', 'url', 'user_agent']);
        });
    }
};
