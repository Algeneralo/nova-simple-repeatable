<?php

namespace Outl1ne\NovaSimpleRepeatable;

use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Nova\Events\ServingNova;
use Laravel\Nova\Nova;
use Outl1ne\NovaTranslationsLoader\LoadsNovaTranslations;

class SimpleRepeatableServiceProvider extends ServiceProvider
{
    use LoadsNovaTranslations;

    public function boot()
    {
        $this->app->booted(function () {
            $this->routes();
        });
        Nova::serving(function (ServingNova $event) {
            Nova::script('simple-repeatable', __DIR__.'/../dist/js/entry.js');
            Nova::style('simple-repeatable', __DIR__.'/../dist/css/entry.css');
        });

        $this->loadTranslations(__DIR__.'/../lang', 'nova-simple-repeatable-field', true);
    }

    public function register()
    {
        //
    }

    protected function routes(): void
    {
        Route::group([
            'domain' => config('nova.domain', null),
            'middleware' => 'nova:api',
            'excluded_middleware' => [SubstituteBindings::class],
        ], function () {
            Route::patch('/nova-api/{resource}/creation-fields', CreationFieldSyncController::class);
        });
    }

}
