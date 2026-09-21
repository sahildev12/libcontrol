<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\File;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $lockPath = storage_path('app/install.lock');

        if (! File::exists($lockPath)) {
            File::ensureDirectoryExists(dirname($lockPath));
            File::put($lockPath, 'installed');
        }
    }
}
