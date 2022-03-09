<?php
namespace Boyo\LinkMobility;

use Illuminate\Support\ServiceProvider;

class LinkMobilityServiceProvider extends ServiceProvider
{
	
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
	        $this->commands([
	            \Boyo\LinkMobility\Commands\Test::class,
	        ]);
	    }
    }
    
    /**
     * Register the application services.
     */
    public function register()
    {
        $this->app->singleton(\Boyo\LinkMobility\LinkMobilitySender::class, function () {
            return new \Boyo\LinkMobility\LinkMobilitySender();
        });
    }
    
}