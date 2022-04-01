<?php
namespace Thiagoprz\CrudTools;

use Illuminate\Support\ServiceProvider;
use Thiagoprz\CrudTools\Commands\MakeCrudController;
use Thiagoprz\CrudTools\Commands\MakeCrudModel;

/**
 * Class CrudToolsServiceProvider
 * @package Thiagoprz\CrudTools
 */
class CrudToolsServiceProvider extends ServiceProvider
{

    /**
     * Register package services
     *
     * @return void
     */
    public function register() {}

    /**
     * Boot package service provider
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeCrudController::class,
                MakeCrudModel::class,
            ]);
        }
        // Publishes CRUD Tools configuration file
        $this->publishes([
            __DIR__ . '/config/crud-tools.php' => config_path('crud-tools.php'),
        ]);
        // Publishes any migrations necessary
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
