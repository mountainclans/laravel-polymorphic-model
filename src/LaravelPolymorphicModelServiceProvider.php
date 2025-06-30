<?php

namespace MountainClans\LaravelPolymorphicModel;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use MountainClans\LaravelPolymorphicModel\Commands\LaravelPolymorphicModelCommand;

class LaravelPolymorphicModelServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-polymorphic-model')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel_polymorphic_model_table')
            ->hasCommand(LaravelPolymorphicModelCommand::class);
    }
}
