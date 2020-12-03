<?php

namespace Nddcoder\ObjectMapper;

use Illuminate\Support\ServiceProvider;
use Nddcoder\ObjectMapper\Commands\ObjectMapperCommand;

class ObjectMapperServiceProvider extends ServiceProvider
{
    public function boot()
    {
        //
    }

    public function register()
    {
        $this->app->bind('laravel-object-mapper', ObjectMapper::class);
    }
}
