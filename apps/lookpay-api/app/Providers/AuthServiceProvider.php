<?php

namespace App\Providers;

use App\Auth\TokenAdmGuard;
use App\Providers\Auth\UserProvider;
use Illuminate\Auth\AuthManager;
use Illuminate\Auth\CreatesUserProviders;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;

class AuthServiceProvider extends \Illuminate\Auth\AuthServiceProvider
{
    use CreatesUserProviders;

    public function register()
    {
        parent::register();
        app(AuthManager::class)->extend('adm_token', function (Application $app, $name, array $config) {
            $provider = app(UserProvider::class);
            $request = app(Request::class);

            $guard = new TokenAdmGuard($provider, $request, $config['input_key']);
            $app->refresh('request', $guard, 'setRequest');

            return $guard;
        });
    }
}
