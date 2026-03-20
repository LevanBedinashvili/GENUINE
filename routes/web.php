<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
})->name('home');

Route::get('/shop', [\App\Http\Controllers\ShopController::class, 'index'])->name('shop');
Route::get('/shop/validate-username', [\App\Http\Controllers\ShopController::class, 'validateUsername'])->name('shop.validate-username');

Route::prefix('payment')->name('payment.')->group(function () {
    Route::post('/create', [\App\Http\Controllers\PaymentController::class, 'createPayment'])
        ->middleware('shop.rate.limit')
        ->name('create');
    
    Route::get('/success/{transaction_id}', [\App\Http\Controllers\PaymentController::class, 'handleRedirect'])
        ->middleware('signed')
        ->name('success');
    
    Route::get('/fail/{transaction_id}', [\App\Http\Controllers\PaymentController::class, 'handleRedirect'])
        ->middleware('signed')
        ->name('fail');
    
    Route::get('/redirect/{transaction_id}', [\App\Http\Controllers\PaymentController::class, 'handleRedirect'])
        ->middleware('signed')
        ->name('redirect');
    
    Route::get('/check/{transaction_id}', [\App\Http\Controllers\PaymentController::class, 'checkStatus'])
        ->name('check');
    
    Route::post('/callback', [\App\Http\Controllers\PaymentController::class, 'handleCallback'])
        ->name('callback');
});
Route::get('/agreement', function () {
    return view('agreement');
})->name('agreement');

Route::get('/privacy', function () {
    return view('privacy');
})->name('privacy');

Route::middleware('guest')->group(function () {
    Route::get('/login', [\App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [\App\Http\Controllers\Auth\LoginController::class, 'login'])->middleware('login.rate.limit');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [\App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout');
});
