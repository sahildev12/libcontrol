<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_registry', function (Blueprint $table) {
            $table->id();
            $table->string('library_code', 12)->unique();
            $table->string('domain');
            $table->string('app_url')->nullable();
            $table->string('client_name')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_registry');
    }
};
