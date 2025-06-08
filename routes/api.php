<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedUserController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\UserPreferenceController;

Route::middleware(['auth:sanctum','throttle:60,1'])->group(function () {
    // User routes
    Route::get('/user', function (Request $request) {
        return $request->user();
    });


    Route::get('/news', [NewsController::class,'index']);
    Route::get('/news/categories', [NewsController::class,'categories']);
    Route::get('/news/authors', [NewsController::class,'authors']);
    Route::get('/news/sources', [NewsController::class,'sources']);

    Route::get('/user/prefrence', [UserPreferenceController::class,'show']);
    Route::post('/user/prefrence', [UserPreferenceController::class,'store']);

    Route::post('/logout', [AuthenticatedUserController::class, 'destroy'])
        ->name('logout');

    Route::post('/reset-password', [NewPasswordController::class, 'store'])
    ->name('password.store');

});



Route::middleware(['throttle:60,1','guest'])->group(function () {

    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->name('register');

    Route::post('/login', [AuthenticatedUserController::class, 'store'])
        ->name('login');

});
