<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('deployment_license_key_hash')->nullable()->index();
            $table->string('deployment_domain')->nullable();
            $table->string('library_code', 16)->nullable()->index();
            $table->string('library_name')->nullable();
            $table->string('subject');
            $table->text('message');
            $table->string('category', 32)->default('general');
            $table->string('status', 32)->default('open')->index();
            $table->string('priority', 16)->default('normal');
            $table->foreignId('reporter_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reporter_name');
            $table->string('reporter_email');
            $table->unsignedBigInteger('remote_id')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};
