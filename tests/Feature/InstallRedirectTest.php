<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class InstallRedirectTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        File::delete(storage_path('app/install.lock'));
    }

    protected function tearDown(): void
    {
        if (File::exists(storage_path('app/install.lock'))) {
            File::delete(storage_path('app/install.lock'));
        }

        parent::tearDown();
    }

    public function test_uninstalled_site_redirects_to_setup(): void
    {
        $this->get('/')
            ->assertRedirect(route('setup.show'));
    }

    public function test_setup_page_is_available_before_install(): void
    {
        $this->get(route('setup.show'))
            ->assertOk()
            ->assertSee('Setup LibControl', false);
    }
}
