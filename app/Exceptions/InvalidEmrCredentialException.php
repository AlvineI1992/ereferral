<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class InvalidEmrCredentialException extends HttpException
{
    public function __construct(string $message = 'Invalid EMR credential. Use the latest generated token with the bearer token for the same EMR-provider account.')
    {
        parent::__construct(403, $message);
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], 403);
    }
}
