<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Auth\AuthenticationException;

class Handler extends ExceptionHandler
{
    // Existing code...

    public function render($request, Throwable $exception)
    {
        if ($exception instanceof AuthenticationException) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'You must be authenticated to access this resource.'
            ], 401);
        }

        return parent::render($request, $exception);
    }
}
