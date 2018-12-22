<?php
namespace Gorankrgovic\LaravelShortable;

use Gorankrgovic\LaravelShortable\Services\ShortService;
use Illuminate\Foundation\Application as LaravelApplication;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Laravel\Lumen\Application as LumenApplication;


/**
 * Class ServiceProvider
 *
 * @package Gorankrgovic\LaravelShortable
 */
class ServiceProvider extends BaseServiceProvider
{

    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->setUpConfig();
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->app->singleton(ShortableObserver::class, function($app) {
            return new ShortableObserver(new ShortService(), $app['events']);
        });
    }


    /**
     * Set up the config
     */
    protected function setUpConfig()
    {
        $source = dirname(__DIR__) . '/resources/config/shortable.php';
        if ($this->app instanceof LaravelApplication) {
            $this->publishes([$source => config_path('shortable.php')], 'config');
        } elseif ($this->app instanceof LumenApplication) {
            $this->app->configure('shortable');
        }
        $this->mergeConfigFrom($source, 'shortable');
    }
}