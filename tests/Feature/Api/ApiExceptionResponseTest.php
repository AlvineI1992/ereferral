<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    config(['app.debug' => true]);
});

test('API HTTP exceptions never expose debug details', function (int $status) {
    Route::get('/api/test-error-response', fn () => abort($status, 'Request denied.', ['Retry-After' => '30']));
    $this->getJson('/api/test-error-response')->assertStatus($status)
        ->assertHeader('Retry-After', '30')
        ->assertExactJson(['message' => 'Request denied.']);
})->with([400, 401, 403, 404, 405, 409, 419, 422, 429]);

test('API validation keeps field errors without debug details', function () {
    Route::post('/api/test-validation-response', fn () => throw ValidationException::withMessages(['facility' => 'Select a facility.']));
    $this->postJson('/api/test-validation-response')->assertUnprocessable()
        ->assertExactJson(['message' => 'Select a facility.', 'errors' => ['facility' => ['Select a facility.']]]);
});

test('API server failures hide internal messages even without JSON accept headers', function () {
    Route::get('/api/test-server-response', fn () => throw new RuntimeException('SQLSTATE secret internal path'));
    $this->get('/api/test-server-response')->assertStatus(500)->assertHeader('Content-Type', 'application/json')
        ->assertExactJson(['message' => 'An unexpected server error occurred. Please try again later.']);
});

test('unknown API routes return clean JSON', function () {
    $response = $this->getJson('/api/does-not-exist')->assertNotFound();
    expect(array_keys($response->json()))->toBe(['message']);
});
