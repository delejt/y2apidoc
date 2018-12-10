<?php namespace Delejt\Y2apidoc;

use Illuminate\Support\ServiceProvider as Sp;
/**
 * Class ServiceProvider
 *
 * @package Delejt\Y2apidoc
 */
class ServiceProvider extends Sp
{
    protected $defer = false;

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/config/config.php', 'y2apidoc');

        // Register Console Commands
        $this->commands(
            Commands\GenerateApiDocs::class
        );

    }
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (function_exists('config_path')) {
            $this->publishes([
                __DIR__.'/config/config.php' => config_path('y2apidoc.php'),
            ], 'config');
        }

    }

}


