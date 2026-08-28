<?php

declare(strict_types=1);

namespace LaraArabDev\RecordkeeperFilament;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

/**
 * Service provider that registers package views for the Filament recordkeeper plugin.
 */
class RecordkeeperFilamentServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-recordkeeper';

    public static string $viewNamespace = 'recordkeeper';

    /**
     * Configure the package name and views.
     *
     * @param  Package  $package  The spatie package builder.
     */
    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasViews(static::$viewNamespace);
    }
}
