<?php

namespace MobileStock\helper\Providers;

use Illuminate\Auth\AuthManager;
use Illuminate\Auth\CreatesUserProviders;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use MobileStock\helper\Auth\TokenAdmGuard;
use MobileStock\helper\Auth\UserProvider;

class AuthServiceProvider extends \Illuminate\Auth\AuthServiceProvider
{
    use CreatesUserProviders;

    public function register()
    {
        parent::register();
        app(AuthManager::class)->extend('token_adm', function (Application $app, $name, array $config) {
            $provider = app(UserProvider::class);
            $request = app(Request::class);

            $guard = new TokenAdmGuard($provider, $request, $config['input_key']);
            $app->refresh('request', $guard, 'setRequest');

            return $guard;
        });
    }

    public function boot()
    {
        $gate = app(Gate::class);
        $gate->define('CLIENTE', function (Authenticatable $user) {
            return preg_match('/\b(10)\b/m', $user->permissao);
        });
        $gate->define('MODO_ATACADO', function (Authenticatable $user) {
            return preg_match('/\b(13)\b/m', $user->permissao);
        });
        $gate->define('FORNECEDOR', function (Authenticatable $user) {
            return preg_match('/\b(3[0-9])\b/m', $user->permissao);
        });
        $gate->define('ADMIN', function (Authenticatable $user) {
            return preg_match('/\b(5[0-9])\b/m', $user->permissao);
        });
        $gate->define('ENTREGADOR', function (Authenticatable $user) {
            return preg_match('/\b(62)\b/m', $user->permissao);
        });
        $gate->define('PONTO_RETIRADA', function (Authenticatable $user) {
            return preg_match('/\b(60)\b/m', $user->permissao);
        });
        $gate->define('TODOS', function (Authenticatable $user) {
            return true;
        });
        $gate->define('FORNECEDOR.CONFERENTE_INTERNO', function (Authenticatable $user) {
            return preg_match('/\b(32)\b/m', $user->permissao);
        });
    }
}
