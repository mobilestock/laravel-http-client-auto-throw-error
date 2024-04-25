<?php

namespace MobileStock\helper\Auth;

use Illuminate\Auth\TokenGuard;

class TokenAdmGuard extends TokenGuard
{
    public function getTokenForRequest()
    {
        $token = $this->request->header($this->inputKey);
        if (empty($token)) {
            $token = parent::getTokenForRequest();
        }

        return $token;
    }
}