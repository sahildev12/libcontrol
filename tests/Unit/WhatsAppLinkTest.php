<?php

namespace Tests\Unit;

use App\Support\WhatsAppLink;
use PHPUnit\Framework\TestCase;

class WhatsAppLinkTest extends TestCase
{
    public function test_builds_wa_me_link_with_country_code(): void
    {
        $url = WhatsAppLink::url('8076105181', 'Hello');

        $this->assertSame('https://wa.me/918076105181?text=Hello', $url);
    }
}
