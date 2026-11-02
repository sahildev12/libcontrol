<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('db_username')->nullable()->after('database_name');
            $table->text('db_password')->nullable()->after('db_username');
            $table->string('owner_name')->nullable()->after('client_name');
            $table->string('owner_email')->nullable()->index()->after('owner_name');
            $table->string('owner_password_hash')->nullable()->after('owner_email');
            $table->dateTime('owner_email_verified_at')->nullable()->after('owner_password_hash');
            $table->string('signup_source', 20)->default('manual')->after('owner_email_verified_at');
            $table->boolean('is_trial')->default(false)->after('plan_tier');
            $table->dateTime('trial_ends_at')->nullable()->after('is_trial');
            $table->dateTime('locked_at')->nullable()->after('trial_ends_at');
            $table->string('status', 20)->default('ready')->index()->after('active');
            $table->string('provision_step', 30)->nullable()->after('status');
            $table->text('provision_error')->nullable()->after('provision_step');
            $table->boolean('hosting_subdomain_created')->default(false)->after('provision_error');
            $table->boolean('hosting_database_created')->default(false)->after('hosting_subdomain_created');
            $table->string('signup_ip', 45)->nullable()->after('hosting_database_created');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropIndex(['owner_email']);
            $table->dropIndex(['status']);
            $table->dropColumn([
                'db_username',
                'db_password',
                'owner_name',
                'owner_email',
                'owner_password_hash',
                'owner_email_verified_at',
                'signup_source',
                'is_trial',
                'trial_ends_at',
                'locked_at',
                'status',
                'provision_step',
                'provision_error',
                'hosting_subdomain_created',
                'hosting_database_created',
                'signup_ip',
            ]);
        });
    }
};
