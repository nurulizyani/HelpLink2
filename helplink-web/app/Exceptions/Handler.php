<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of inputs that are never flashed for validation exceptions.
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register any exception handling callbacks.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Handle unauthenticated user redirects.
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        // ✅ If admin route, redirect to admin login
        if ($request->is('admin') || $request->is('admin/*')) {
            return redirect()->guest(route('admin.login'));
        }

        // 👤 Otherwise redirect to normal user login
        return redirect()->guest(route('login'));
    }
}
