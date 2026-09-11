<?php

namespace App\Services;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiExceptionResponse
{
    public function render(Response $response, \Throwable $exception, Request $request): Response
    {
        if (! $request->is('api', 'api/*') || $response->getStatusCode() < 400) {
            return $response;
        }

        $status = $response->getStatusCode();
        $original = json_decode($response->getContent(), true);
        $data = ['message' => $status >= 500
            ? 'An unexpected server error occurred. Please try again later.'
            : (is_array($original) && is_string($original['message'] ?? null)
                ? $original['message']
                : (Response::$statusTexts[$status] ?? 'Request failed.'))];

        if ($status < 500 && is_array($original)) {
            foreach (['error', 'errors'] as $field) {
                if (array_key_exists($field, $original)) {
                    $data[$field] = $original[$field];
                }
            }
        }

        $response->setContent(json_encode($data, JSON_THROW_ON_ERROR));
        $response->headers->set('Content-Type', 'application/json');
        $response->headers->remove('Content-Length');

        return $response;
    }
}
