<?php

namespace Delejt\Y2apidoc;

use Illuminate\Support\ServiceProvider;

/**
 * Class Y2apidocServiceProvider
 *
 * @package Delejt\Y2apidoc
 */
class Y2apidocServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/config/y2apidoc.php', 'y2apidoc'
        );

        // Register Console Commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\GenerateApiDocs::class,
            ]);
        }

    }
}
