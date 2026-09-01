<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licensed_deployments', function (Blueprint $table) {
            $table->id();
            $table->string('client_name');
            $table->string('license_key_hash', 64)->unique();
            $table->json('allowed_domains');
            $table->unsignedSmallInteger('grace_days')->default(7);
            $table->boolean('active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('installation_events', function (Blueprint $table) {
            $table->id();
            $table->string('license_key_hash', 64)->index();
            $table->string('domain');
            $table->string('app_url')->nullable();
            $table->string('fingerprint', 64)->index();
            $table->string('server_ip', 45)->nullable();
            $table->string('php_version', 32)->nullable();
            $table->string('app_version', 32)->nullable();
            $table->boolean('is_authorized')->default(false);
            $table->dateTime('first_seen_at');
            $table->dateTime('last_seen_at');
            $table->unsignedInteger('hit_count')->default(1);
            $table->timestamps();

            $table->unique(['license_key_hash', 'fingerprint']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installation_events');
        Schema::dropIfExists('licensed_deployments');
    }
};
