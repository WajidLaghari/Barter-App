<?php

use App\Http\Controllers\API\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::controller(UserController::class)->prefix('auth')->group(function () {
    Route::post('/login', 'login');
    Route::post('/register', 'register');

    Route::middleware(['auth:sanctum', 'admin'])->group(function () {
        Route::delete('/delete/{user}', 'delete');
    });

    // Route::middleware('auth:sanctum')->group(function () {
    //     Route::get('/users', 'index');
    //     Route::get('/logout', 'invalidateLogin');
    //     Route::get('/logout', 'logout');
    //     Route::put('/update/{id}', 'update');
    //     Route::post('/update-password/{id}', 'updatePassword');
    // });
});
Route::middleware('auth:sanctum')->group(function () {
    Route::controller(UserController::class)->group(function () {
        Route::put('/update/{id}', 'update');
        // Add more related routes here as needed
    });
});

