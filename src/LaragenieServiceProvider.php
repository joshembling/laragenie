<?php

namespace JoshEmbling\Laragenie;

use Spatie\LaravelPackageTools\Package;
use JoshEmbling\Laragenie\Commands\LaragenieCommand;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\LaravelPackageTools\Commands\InstallCommand;

class LaragenieServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laragenie')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laragenie_table')
            ->hasCommand(LaragenieCommand::class)
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->askToStarRepoOnGitHub('joshembling/laragenie');
            });
    }
}
