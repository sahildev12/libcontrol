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
        Config::set('libcontrol.tenancy.landlord_hosts', ['libcontrol.in']);
        Config::set('libcontrol.marketing_site.enabled', true);
        Config::set('libcontrol.marketing_site.hosts', ['libcontrol.in']);
    }

    public function test_hub_host_root_serves_libcontrol_marketing_site(): void
    {
        $response = $this->get('http://libcontrol.in/');
        $response->assertOk();
        $this->assertMarketingHtmlContains($response, 'Katsindia');
        $this->assertMarketingHtmlContains($response, 'study seat management');
    }

    public function test_index_html_serves_marketing_home_for_contact_links(): void
    {
        $response = $this->get('http://libcontrol.in/index.html');
        $response->assertOk();
        $this->assertMarketingHtmlContains($response, 'id="support"');
    }

    public function test_landlord_marketing_assets_are_available(): void
    {
        TenantContext::setLandlordMode();

        $this->get('http://libcontrol.in/styles.css')
            ->assertOk()
            ->assertHeader('content-type', 'text/css; charset=UTF-8');

        $this->get('http://libcontrol.in/assets/favicon-light-32x32.png')
            ->assertOk()
            ->assertHeader('content-type', 'image/png');

        $this->get('http://libcontrol.in/favicon.ico')
            ->assertOk()
            ->assertHeader('content-type', 'image/x-icon');
    }

    public function test_hub_host_serves_marketing_even_when_tenancy_is_disabled(): void
    {
        Config::set('libcontrol.tenancy.enabled', false);

        $this->get('http://libcontrol.in/documentation.html')
            ->assertOk();
    }

    private function assertMarketingHtmlContains(\Illuminate\Testing\TestResponse $response, string $needle): void
    {
        $base = $response->baseResponse;

        if ($base instanceof \Symfony\Component\HttpFoundation\BinaryFileResponse) {
            $path = $base->getFile()->getPathname();
            $this->assertStringContainsString($needle, (string) file_get_contents($path));

            return;
        }

        $response->assertSee($needle, false);
    }

}
