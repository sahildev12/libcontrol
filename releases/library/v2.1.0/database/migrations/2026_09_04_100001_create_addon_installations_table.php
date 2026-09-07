<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addon_installations', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->string('version', 32);
            $table->boolean('enabled')->default(true);
            $table->json('settings')->nullable();
            $table->timestamp('installed_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addon_installations');
    }
};
