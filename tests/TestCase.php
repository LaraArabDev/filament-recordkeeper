<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentRecordkeeper\Tests;

use LaraArabDev\FilamentRecordkeeper\FilamentRecordkeeperServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            FilamentRecordkeeperServiceProvider::class,
        ];
    }
}
