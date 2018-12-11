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
        $this->publishes([
            __DIR__.'/config/config.php' => config_path('y2apidoc.php'),
        ], 'config');

        $this->publishes([
            __DIR__.'/templates' => resource_path('views/vendor/y2apidoc'),
        ], 'template');

        view()->addLocation(config('y2apidoc.documentation.source'));
        view()->addLocation(config('y2apidoc.documentation.languages'));
        view()->addLocation(config('y2apidoc.documentation.tags_template_path'));
    }

}


