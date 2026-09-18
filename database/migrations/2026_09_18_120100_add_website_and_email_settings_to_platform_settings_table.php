<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->boolean('website_enabled')->default(false)->after('id_card_logo_path');
            $table->string('website_tagline')->nullable()->after('website_enabled');
            $table->string('website_hero_title')->nullable()->after('website_tagline');
            $table->text('website_about')->nullable()->after('website_hero_title');
            $table->json('website_amenities')->nullable()->after('website_about');
            $table->json('website_social_links')->nullable()->after('website_amenities');
            $table->string('website_whatsapp', 20)->nullable()->after('website_social_links');
            $table->string('website_logo_path')->nullable()->after('website_whatsapp');

            $table->boolean('email_welcome_enabled')->default(true)->after('website_logo_path');
            $table->boolean('email_birthday_enabled')->default(true)->after('email_welcome_enabled');
            $table->boolean('email_offers_enabled')->default(false)->after('email_birthday_enabled');
            $table->boolean('email_marketing_enabled')->default(false)->after('email_offers_enabled');
            $table->boolean('email_recovery_enabled')->default(true)->after('email_marketing_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->dropColumn([
                'website_enabled',
                'website_tagline',
                'website_hero_title',
                'website_about',
                'website_amenities',
                'website_social_links',
                'website_whatsapp',
                'website_logo_path',
                'email_welcome_enabled',
                'email_birthday_enabled',
                'email_offers_enabled',
                'email_marketing_enabled',
                'email_recovery_enabled',
            ]);
        });
    }
};
