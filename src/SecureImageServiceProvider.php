<?php

namespace Gourabsutradhar\SecureImage;

use Illuminate\Support\Facades\Validator;
//use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Class SecureImageServiceProvider
 */
class SecureImageServiceProvider extends ServiceProvider
{
    /**
     * Boot the service provider.
     */
    public function boot(): void
    {
        // Publish configuration files
        $this->publishes([
            __DIR__.'/../config/secureimage.php' => config_path('secureimage.php'),
        ], 'secureimage-config');

        //register routes
        $this->app['router']->get('/api/secureimage', 'Gourabsutradhar\SecureImage\SecureImageController@getSecureImageApi')->middleware('api')->name('secureimage.api');
        //$this->app['router']->get('/secureimage', 'Gourabsutradhar\SecureImage\SecureImageController@getSecureImage')->middleware('web')->name('secureimage');

        //add validator rule extension
        //for api
        //example rule 'secureimage'=>'required|secureimage_rule:key,code'
        Validator::extend('secureimage_api', function ($attribute, $value, $parameters, $validator) {
            return (new Secureimage)->verify($value, $parameters[0]);
        });
        //for web
        Validator::extend('secureimage_web', function ($attribute, $value, $parameters, $validator) {
            return dd((new Secureimage)->verify($value, session('secureimage')));
        });

    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        // Merge configs
        $this->mergeConfigFrom(
            __DIR__.'/../config/secureimage.php',
            'secureimage'
        );
    }
}
