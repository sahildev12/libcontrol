<?php

namespace Tests\Feature;

use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class LandlordMarketingSiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('libcontrol.tenancy.enabled', true);
        Config::set('libcontrol.tenancy.landlord_hosts', ['libcontrol.phenomit.com']);
        Config::set('libcontrol.marketing_site.enabled', true);
        Config::set('libcontrol.marketing_site.hosts', ['libcontrol.phenomit.com']);
    }

    public function test_hub_host_root_serves_libcontrol_marketing_site(): void
    {
        $this->get('http://libcontrol.phenomit.com/')
            ->assertOk()
            ->assertSee('Run every seat', false)
            ->assertDontSee('A Quiet Place For Bigger Dreams', false);
    }

    public function test_landlord_marketing_assets_are_available(): void
    {
        TenantContext::setLandlordMode();

        $this->get('http://libcontrol.phenomit.com/styles.css')
            ->assertOk()
            ->assertHeader('content-type', 'text/css; charset=UTF-8');
    }

    public function test_hub_host_serves_marketing_even_when_tenancy_is_disabled(): void
    {
        Config::set('libcontrol.tenancy.enabled', false);

        $this->get('http://libcontrol.phenomit.com/documentation.html')
            ->assertOk();
    }
}
