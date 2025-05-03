<?php


// app/Exceptions/Handler.php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    // Other methods...

    /**
     * Convert an authentication exception into an unauthenticated response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Auth\AuthenticationException  $exception
     * @return \Illuminate\Http\Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->is('api/*')) {
            // Return JSON response for API requests
            return response()->json([
                'error' => 'Unauthenticated',
                'message' => 'Please login to access this resource.'
            ], 401);
        }

        // Default behavior for web requests (redirect to login page)
        return redirect()->guest(route('login'));
    }
}

