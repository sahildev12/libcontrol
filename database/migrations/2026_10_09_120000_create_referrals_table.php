<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('referrer_student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('enquiry_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('referred_student_id')->nullable()->unique()->constrained('students')->nullOnDelete();
            $table->string('referred_name');
            $table->string('referred_phone', 20)->nullable();
            $table->string('status', 20)->default('enquiry');
            $table->string('reward_status', 20)->default('not_due');
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('reward_given_at')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'status']);
        });

        Schema::table('library_growth_profiles', function (Blueprint $table) {
            $table->string('google_maps_url', 500)->nullable()->after('gbp_hours');
        });
    }

    public function down(): void
    {
        Schema::table('library_growth_profiles', function (Blueprint $table) {
            $table->dropColumn('google_maps_url');
        });

        Schema::dropIfExists('referrals');
    }
};
