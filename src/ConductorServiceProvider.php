<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

final class ConductorServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('conductor')
            ->hasConfigFile()
            ->hasViews();
    }
}