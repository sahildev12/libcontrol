<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class MarketingCookieConsentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('libcontrol.tenancy.enabled', false);
        Config::set('libcontrol.marketing_site.enabled', true);
        Config::set('libcontrol.marketing_site.hosts', ['libcontrol.in']);
    }

    public function test_cookie_consent_script_is_served(): void
    {
        $this->get('http://libcontrol.in/cookie-consent.js')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/javascript; charset=UTF-8');
    }

    public function test_trackers_only_load_through_consent_script(): void
    {
        $html = $this->servedHtml('index.html');

        $this->assertStringContainsString('/cookie-consent.js', $html);
        $this->assertStringNotContainsString('googletagmanager.com/gtag/js', $html);
        $this->assertStringNotContainsString('fbevents.js', $html);
    }

    public function test_legal_pages_load_with_consent_script_and_refund_policy_link(): void
    {
        foreach (['privacy-policy.html', 'terms-and-conditions.html', 'disclaimer.html', 'refund-policy.html'] as $page) {
            $html = $this->servedHtml($page);

            $this->assertStringContainsString('cookie-consent.js', $html);
            $this->assertStringContainsString('href="refund-policy.html"', $html);
        }

        $this->assertStringContainsString('<h1>Refund Policy</h1>', $this->servedHtml('refund-policy.html'));
    }

    private function servedHtml(string $page): string
    {
        $response = $this->get('http://libcontrol.in/'.$page)->assertOk();

        return file_get_contents($response->baseResponse->getFile()->getPathname());
    }
}
