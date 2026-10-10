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

    public function test_every_page_uses_the_full_site_footer(): void
    {
        $pages = ['index.html', 'documentation.html', 'support-articles.html', 'privacy-policy.html', 'terms-and-conditions.html', 'disclaimer.html', 'refund-policy.html'];

        foreach ($pages as $page) {
            $html = $this->servedHtml($page);

            $this->assertStringContainsString('<h3>Guide</h3>', $html, $page);
            $this->assertStringContainsString('href="disclaimer.html', $html, $page);
            $this->assertStringContainsString('Mon – Sat 9.00 – 18.00', $html, $page);
            $this->assertStringContainsString('lc-trust-bar', $html, $page);
        }
    }

    public function test_robots_txt_points_to_the_sitemap(): void
    {
        $robots = file_get_contents(public_path('robots.txt'));

        $this->assertStringContainsString('Sitemap: https://libcontrol.in/sitemap.xml', $robots);
        $this->assertStringContainsString('Allow: /', $robots);
    }

    public function test_sitemap_lists_every_public_page(): void
    {
        $xml = file_get_contents(public_path('sitemap.xml'));
        $this->assertNotFalse(simplexml_load_string($xml));

        foreach (['', 'documentation.html', 'support-articles.html', 'privacy-policy.html', 'terms-and-conditions.html', 'refund-policy.html', 'disclaimer.html'] as $page) {
            $this->assertStringContainsString('<loc>https://libcontrol.in/'.$page.'</loc>', $xml);
            $this->get('http://libcontrol.in/'.$page)->assertOk();
        }
    }

    private function servedHtml(string $page): string
    {
        $response = $this->get('http://libcontrol.in/'.$page)->assertOk();

        return file_get_contents($response->baseResponse->getFile()->getPathname());
    }
}
