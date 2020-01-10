<?php

namespace DaVikingCode\LaravelAlexV2Api;

use DaVikingCode\LaravelAlexV2Api\Controllers\LaravelAlexV2ApiController;
use Illuminate\Support\ServiceProvider;

class LaravelAlexV2ApiServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'davikingcode');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'davikingcode');
        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        // $this->loadRoutesFrom(__DIR__.'/routes.php');

        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/laravelalexv2api.php', 'laravelalexv2api');

        // Register the service the package provides.
        $this->app->singleton('laravelalexv2api', function ($app) {
            return new LaravelAlexV2ApiController();
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['laravelalexv2api'];
    }

    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole()
    {
        // Publishing the configuration file. ($ php artisan vendor:publish --tag=alexv2api.config)
        $this->publishes([
            __DIR__.'/../config/laravelalexv2api.php' => config_path('laravelalexv2api.php'),
        ], 'laravelalexv2api.config');

        // Publishing the views.
        /*$this->publishes([
            __DIR__.'/../resources/views' => base_path('resources/views/vendor/davikingcode'),
        ], 'alexv2api.views');*/

        // Publishing assets.
        /*$this->publishes([
            __DIR__.'/../resources/assets' => public_path('vendor/davikingcode'),
        ], 'alexv2api.views');*/

        // Publishing the translation files.
        /*$this->publishes([
            __DIR__.'/../resources/lang' => resource_path('lang/vendor/davikingcode'),
        ], 'alexv2api.views');*/

        // Registering package commands.
        // $this->commands([]);
    }
}
