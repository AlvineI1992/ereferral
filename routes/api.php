<?php
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Auth\RegisteredUserController;


Route::get('/test', function () {
    return response()->json(['message' => 'API is working']);
});

Route::get('/api/users', [RegisteredUserController::class, 'index']);

require __DIR__.'/auth.php';




