<?php namespace Syscover\Navtools;

use Illuminate\Support\ServiceProvider;
use Syscover\Navtools\Lib\Redirector;

class NavtoolsServiceProvider extends ServiceProvider
{
	/**
	 * Bootstrap the application services.
	 *
	 * @return void
	 */
	public function boot()
	{
        // register config files
        $this->publishes([
            __DIR__ . '/../../config/pulsar.navtools.php' => config_path('pulsar.navtools.php')
        ]);
	}

	/**
	 * Register the application services.
	 *
	 * @return void
	 */
	public function register()
	{
        // Extend core class Illuminate\Routing\Redirector
	    $this->app->bind('redirect', function($app)
        {
            return new Redirector($app->make(\Illuminate\Routing\UrlGenerator::class));
        });
	}
}