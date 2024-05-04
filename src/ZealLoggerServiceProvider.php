<?php

namespace Zeal\Logger;


use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Zeal\Logger\Commands\ZealLoggerCommand;
use Zeal\Logger\Core\DTO\RequestLoggerDto;
use Zeal\Logger\Core\Middleware\LoggerMiddleware;

class ZealLoggerServiceProvider extends PackageServiceProvider
{
    public function register()
    {
        $this->app->singleton(LoggerService::class, function (Application $app) {
            return new LoggerService(
                RequestLoggerDto::fromRequest($app->make(Request::class)),
            );
        });

        $this->app->singleton(LoggerMiddleware::class);
         parent::register();
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name('zeal-logger')
            ->hasConfigFile()
            ->hasMigration('create_api_logs_table')
            ->hasCommand(ZealLoggerCommand::class);
    }
}
