<?php

namespace Netkovk\GoogleMapGeocoding;

use Illuminate\Support\ServiceProvider;

class GoogleMapGeocodingProvider extends ServiceProvider
{

    /**
    * Indicates if loading of the provider is deferred.
    *
    * @var bool
    */
    protected $defer = false;

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes(
            [
                __DIR__ . '/config/config.php' => config_path('googlemapgeocoding.php'),
            ], 'config'
        );
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $configPath = __DIR__ . '/config/config.php';
        $this->mergeConfigFrom($configPath, 'googlemapgeocoding');

        $this->app->bind('Netkovk\GoogleMapGeocoding\GoogleMap', function($app){
            return new GoogleMap($app['config']->get('googlemapgeocoding'));
        });
    }
}
