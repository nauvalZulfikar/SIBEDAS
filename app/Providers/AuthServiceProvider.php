<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use App\Auth\CachedUserProvider;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [];

    public function boot(): void
    {
        Auth::provider('cached-eloquent', function ($app, array $config) {
            return new CachedUserProvider($app['hash'], $config['model']);
        });
    }
}
