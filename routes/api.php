<?php

use App\Http\Controllers\API\ItemController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\ConversationController;
use App\Http\Controllers\API\MessageController;
use App\Http\Controllers\API\OfferController;
use App\Http\Controllers\API\TransactionController;
use App\Http\Controllers\API\UserVerificationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReviewController;
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
        #Admin-only routes
        Route::post('/create/subAdmin', [UserController::class, 'createSubAdmin']);
        Route::get('/show-subAdmins', [UserController::class, 'showSubAdmins']);
        Route::delete('/delete-subAdmin/{id}', [UserController::class, 'deleteSubAdmin']);
        Route::get('/inactive-subAdmin', [UserController::class, 'inactiveSubAdmins']);
        Route::put('/restore-subAdmin/{id}', [UserController::class, 'restoreSubAdmin']);
        Route::delete('/permenant-delete-subAdmin/{id}', [UserController::class, 'permanentDeleteSubAdmin']);
    });

    Route::middleware(['auth:sanctum', 'adminOrSubAdmin'])->group(function () {
        #Shared routes for admin and sub-admin
        Route::get('/show-users', [UserController::class, 'showUsers']);
        Route::get('/specified-user/{id}', [UserController::class, 'show']);
        Route::delete('/delete/{id}', [UserController::class, 'delete']);
        Route::get('/inactive-users', [UserController::class, 'inactiveUsers']);
        Route::put('/restore-user/{id}', [UserController::class, 'restoreUser']);
        Route::delete('/permenant-delete-user/{id}', [UserController::class, 'permanentDeleteUser']);

        Route::apiResource('/categories', CategoryController::class)->except(['index', 'show']);

        #Item approval or rejection
        Route::put('/item/approveORreject/{id}', [UserController::class, 'isApproved']);

        #All items for a user
        Route::get('/User/items', [UserController::class, 'showItems']);

        #Offer Routes
        Route::get('/offers', [OfferController::class, 'index']);

        #User Verification Route
        Route::post('/handle-verification/{id}', [UserVerificationController::class, 'handleVerification']);
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/logout', 'logout');
        Route::put('/update/{id}', 'update');
        Route::put('/update-password/{id}', 'updatePassword');

        Route::apiResource('/items', ItemController::class);
        Route::apiResource('/offers', OfferController::class);
        Route::apiResource('/conversations', ConversationController::class);
        Route::post('/verify-profile', [UserVerificationController::class, 'verifyProfile']);

        Route::post('/create-transaction', [TransactionController::class, 'createTransaction']);
        Route::put('/update-status/{id}', [TransactionController::class, 'updateStatus']);
        Route::get('/show-transactions', [TransactionController::class, 'showTransaction']);
        Route::get('/show-specified-transaction/{id}', [TransactionController::class, 'showSpecifiedTransaction']);
        Route::delete('/delete-transaction/{id}', [TransactionController::class, 'deleteTransaction']);

        Route::post('/send-message', [MessageController::class, 'send']);
        Route::put('/message/{messageId}/read', [MessageController::class, 'markAsRead']);

        Route::get('/categories', [CategoryController::class, 'index']);
        Route::get('/categories/{id}', [CategoryController::class, 'show']);

        Route::get('/my-profile', [UserController::class, 'myProfile']);

        Route::post('send-notification', [NotificationController::class, 'sendUserNotification']);
        Route::get('get-notification', [NotificationController::class, 'getUserNotifications']);

        // Route::resource('/reviews', ReviewController::class);
        Route::post('/store/reviews', [ReviewController::class, 'store']);

        Route::get('show/all/items/for/user',[ItemController::class,'showAllForUser']);

        Route::get('/items-with-offers', [ItemController::class, 'itemsWithOffers']);

        Route::get('/item-with-offers', [ItemController::class, 'itemWithOffersDetails']);

        Route::get('/offers/{id}', [OfferController::class, 'viewOfferDetail']);

    });
});
