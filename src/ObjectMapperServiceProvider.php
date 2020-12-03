<?php

namespace Nddcoder\ObjectMapper;

use Illuminate\Support\ServiceProvider;

class ObjectMapperServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        //
    }

    public function register(): void
    {
        $this->app->bind('laravel-object-mapper', ObjectMapper::class);
    }
}
