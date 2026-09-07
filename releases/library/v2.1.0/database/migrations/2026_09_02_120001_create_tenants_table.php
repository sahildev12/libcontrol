<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('subdomain')->unique();
            $table->string('client_name');
            $table->string('database_name');
            $table->string('plan_tier')->default('starter');
            $table->unsignedInteger('max_seats_override')->nullable();
            $table->unsignedInteger('max_halls_override')->nullable();
            $table->unsignedInteger('max_branches_override')->nullable();
            $table->boolean('active')->default(true);
            $table->text('notes')->nullable();
            $table->dateTime('provisioned_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
