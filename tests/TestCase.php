<?php

declare(strict_types=1);

namespace LaraArabDev\RecordkeeperFilament\Tests;

use LaraArabDev\Recordkeeper\RecordkeeperServiceProvider;
use LaraArabDev\RecordkeeperFilament\RecordkeeperFilamentServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            RecordkeeperServiceProvider::class,
            RecordkeeperFilamentServiceProvider::class,
        ];
    }
}
