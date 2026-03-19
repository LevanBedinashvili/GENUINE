<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
})->name('home');

Route::get('/shop', [\App\Http\Controllers\ShopController::class, 'index'])->name('shop');

Route::prefix('payment')->name('payment.')->group(function () {
    Route::post('/create', [\App\Http\Controllers\PaymentController::class, 'createPayment'])->name('create');
    
    Route::get('/success/{transaction_id}', [\App\Http\Controllers\PaymentController::class, 'handleRedirect'])
        ->name('success');
    
    Route::get('/fail/{transaction_id}', [\App\Http\Controllers\PaymentController::class, 'handleRedirect'])
        ->name('fail');
    
    Route::get('/redirect/{transaction_id}', [\App\Http\Controllers\PaymentController::class, 'handleRedirect'])
        ->name('redirect');
    
    Route::get('/check/{transaction_id}', [\App\Http\Controllers\PaymentController::class, 'checkStatus'])
        ->name('check');
    
    Route::post('/callback', [\App\Http\Controllers\PaymentController::class, 'handleCallback'])
        ->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
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
    Route::post('/login', [\App\Http\Controllers\Auth\LoginController::class, 'login']);
    
    Route::get('/register', [\App\Http\Controllers\Auth\RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [\App\Http\Controllers\Auth\RegisterController::class, 'register']);
    
    Route::get('/password/reset', [\App\Http\Controllers\Auth\ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/password/email', [\App\Http\Controllers\Auth\ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/password/reset/{token}', [\App\Http\Controllers\Auth\ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/password/reset', [\App\Http\Controllers\Auth\ResetPasswordController::class, 'reset'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [\App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout');
});

Route::middleware(['auth'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Admin\AdminDashboardController::class, 'index'])
        ->name('admin.dashboard');

    Route::prefix('transactions')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\AdminDashboardController::class, 'transactions'])
            ->name('admin.transactions.index');
        
        Route::post('/search-player', [\App\Http\Controllers\Admin\AdminDashboardController::class, 'searchPlayer'])
            ->name('admin.transactions.search');
        
        Route::post('/{id}/approve', [\App\Http\Controllers\Admin\AdminDashboardController::class, 'verifyAndApprove'])
            ->name('admin.transactions.approve');
        
        Route::post('/{id}/fail', [\App\Http\Controllers\Admin\AdminDashboardController::class, 'markAsFailed'])
            ->name('admin.transactions.fail');
    });

    Route::prefix('shop/items')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\ShopItemController::class, 'index'])
            ->name('admin.shop.items.index');
        
        Route::get('/create', [\App\Http\Controllers\Admin\ShopItemController::class, 'create'])
            ->name('admin.shop.items.create');
        
        Route::post('/', [\App\Http\Controllers\Admin\ShopItemController::class, 'store'])
            ->name('admin.shop.items.store');
        
        Route::get('/{item}/edit', [\App\Http\Controllers\Admin\ShopItemController::class, 'edit'])
            ->name('admin.shop.items.edit');
        
        Route::put('/{item}', [\App\Http\Controllers\Admin\ShopItemController::class, 'update'])
            ->name('admin.shop.items.update');
        
        Route::delete('/{item}', [\App\Http\Controllers\Admin\ShopItemController::class, 'destroy'])
            ->name('admin.shop.items.destroy');
        
        Route::post('/sort', [\App\Http\Controllers\Admin\ShopItemController::class, 'updateSortOrder'])
            ->name('admin.shop.items.sort');
    });

    Route::post('/api/transactions', [\App\Http\Controllers\Admin\TransactionController::class, 'store'])
        ->name('api.transactions.store');
    
    Route::get('/api/transactions/{externalTxId}', [\App\Http\Controllers\Admin\TransactionController::class, 'getByExternalId'])
        ->name('api.transactions.get');
});
