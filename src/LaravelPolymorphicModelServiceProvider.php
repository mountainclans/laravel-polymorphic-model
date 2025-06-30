<?php

namespace MountainClans\LaravelPolymorphicModel;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use MountainClans\LaravelPolymorphicModel\Commands\LaravelPolymorphicModelCommand;

class LaravelPolymorphicModelServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name('laravel-polymorphic-model');
    }
}
