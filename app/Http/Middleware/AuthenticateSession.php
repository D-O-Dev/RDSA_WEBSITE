<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;

class AuthenticateSession extends \Illuminate\Session\Middleware\AuthenticateSession
{
    protected function redirectTo(Request $request): ?string
    {
        return $request->expectsJson() ? null : route('login');
    }
}
