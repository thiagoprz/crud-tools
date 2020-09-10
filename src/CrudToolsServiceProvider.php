<?php
namespace Thiagoprz\CrudTools;

use Illuminate\Support\ServiceProvider;
use Thiagoprz\CrudTools\Commands\MakeCrudController;

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
            ]);
        }
        // Publishes CRUD Tools configuration file
        $this->publishes([
            __DIR__ . '/../config/crud-tools.php' => config_path('crud-tools.php'),
        ]);
    }
}