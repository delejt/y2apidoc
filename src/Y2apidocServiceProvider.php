<?php

namespace Delejt\Y2apidoc;

use Illuminate\Support\ServiceProvider;
//use phpDocumentor\Reflection\DocBlock\Tag;

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
