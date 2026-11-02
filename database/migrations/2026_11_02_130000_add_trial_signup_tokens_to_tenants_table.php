<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('signup_token', 64)->nullable()->unique()->after('signup_ip');
            $table->string('login_token_hash', 64)->nullable()->after('signup_token');
            $table->dateTime('login_token_expires_at')->nullable()->after('login_token_hash');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropUnique(['signup_token']);
            $table->dropColumn(['signup_token', 'login_token_hash', 'login_token_expires_at']);
        });
    }
};
