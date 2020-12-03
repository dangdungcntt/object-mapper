<?php

namespace Nddcoder\ObjectMapper;

use Illuminate\Support\ServiceProvider;

class ObjectMapperServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/laravel-object-mapper.php' => config_path('laravel-object-mapper.php'),
            ], 'config');
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/laravel-object-mapper.php', 'laravel-object-mapper');
        $this->app->bind('laravel-object-mapper', ObjectMapper::class);
    }
}
