<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class AttendanceTestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $sqlite = [
            'driver' => 'sqlite',
            'database' => 'file:attendance-testing?mode=memory&cache=shared',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ];

        $app = require Application::inferBasePath().'/bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();

        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', $sqlite);
        $app['config']->set('database.connections.mysql', $sqlite);
        $app['config']->set('libcontrol.tenancy.enabled', false);

        return $app;
    }
}
