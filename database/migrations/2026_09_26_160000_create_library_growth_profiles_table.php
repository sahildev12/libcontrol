<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_growth_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->boolean('gbp_claimed')->default(false);
            $table->boolean('gbp_photos')->default(false);
            $table->boolean('gbp_category')->default(false);
            $table->boolean('gbp_hours')->default(false);
            $table->unsignedInteger('google_review_count')->default(0);
            $table->string('google_review_url')->nullable();
            $table->string('facebook_url')->nullable();
            $table->string('instagram_url')->nullable();
            $table->timestamp('last_whatsapp_campaign_at')->nullable();
            $table->timestamp('last_review_campaign_at')->nullable();
            $table->boolean('referral_campaign_active')->default(false);
            $table->string('referral_offer_text')->nullable();
            $table->string('active_package')->nullable();
            $table->timestamp('package_active_until')->nullable();
            $table->unsignedTinyInteger('score_cached')->default(0);
            $table->timestamp('scored_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique('branch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_growth_profiles');
    }
};
