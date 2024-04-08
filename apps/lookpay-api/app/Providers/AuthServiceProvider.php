<?php

namespace App\Providers;

use Illuminate\Auth\CreatesUserProviders;

class AuthServiceProvider extends \Illuminate\Auth\AuthServiceProvider
{
    use CreatesUserProviders;

    public function register()
    {
        parent::register();
    }
}
