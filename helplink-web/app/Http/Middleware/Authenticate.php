<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo($request): ?string
    {
        // ✅ Kalau request datang dari API (contohnya /api/...),
        // jangan redirect ke HTML, terus bagi response JSON.
        if ($request->is('api/*')) {
            abort(response()->json(['error' => 'Unauthorized'], 401));
        }

        // ✅ Kalau URL mula dengan /admin → redirect ke admin login
        if ($request->is('admin') || $request->is('admin/*')) {
            return route('admin.login');
        }

        // ✅ Kalau bukan API dan bukan admin → redirect ke user login
        return route('login');
    }
}
