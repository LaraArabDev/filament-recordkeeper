<?php

declare(strict_types=1);

namespace LaraArabDev\RecordkeeperFilament;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

/**
 * Registers package views for the Filament recordkeeper plugin.
 */
class RecordkeeperFilamentServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-recordkeeper';

    public static string $viewNamespace = 'recordkeeper';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasViews(static::$viewNamespace)
            ->hasTranslations();
    }
}
