<?php

use App\Http\Controllers\API\ItemController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\CategoryController;
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
});

Route::controller(UserController::class)->group(function () {

    Route::middleware(['auth:sanctum', 'admin'])->group(function () {
        Route::get('/show-users', 'index');
        Route::get('/specified-user/{id}', 'show');
        Route::delete('/delete/{id}', 'delete');
        Route::get('/inactive-users', 'inactiveUsers');
        Route::put('/restore-user/{id}', 'restoreUser');
        Route::delete('/permenant-delete-user/{id}', 'permanentDeleteUser');
    });

    Route::apiResource('categories', CategoryController::class);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::controller(UserController::class)->group(function () {
        Route::get('/logout', 'logout');
        Route::put('/update/{id}', 'update');
        Route::put('/update-password/{id}', 'updatePassword');
    });
});

Route::apiResource('items', ItemController::class)->middleware(['auth:sanctum']);

