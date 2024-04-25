<?php

namespace MobileStock\helper\Auth;

use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Auth;

trait QueueAuth
{
    protected array $authData;
    protected array $authKeys;

    public function __sleep()
    {
        $user = Auth::user();
        foreach ($this->authKeys as $key) {
            $this->authData[$key] = $user->$key;
        }
        unset($this->authKeys);

        return array_keys(get_object_vars($this));
    }

    public function __wakeup(): void
    {
        Auth::setUser(new GenericUser($this->authData));
        unset($this->authData);
    }
}
