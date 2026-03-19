<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin.auth'])->group(function () {
    Route::get('/', function () {
        return view('admin.dashboard');
    })->name('dashboard');

    Route::get('/dashboard', [\App\Http\Controllers\Admin\AdminDashboardController::class, 'index'])
        ->name('admin.dashboard');

    Route::prefix('transactions')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\AdminDashboardController::class, 'transactions'])
            ->name('transactions.index');
        
        Route::post('/search-player', [\App\Http\Controllers\Admin\AdminDashboardController::class, 'searchPlayer'])
            ->name('transactions.search');
        
        Route::post('/{id}/approve', [\App\Http\Controllers\Admin\AdminDashboardController::class, 'verifyAndApprove'])
            ->name('transactions.approve');
        
        Route::post('/{id}/fail', [\App\Http\Controllers\Admin\AdminDashboardController::class, 'markAsFailed'])
            ->name('transactions.fail');
    });

    Route::prefix('shop/items')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\ShopItemController::class, 'index'])
            ->name('shop.items.index');
        
        Route::get('/create', [\App\Http\Controllers\Admin\ShopItemController::class, 'create'])
            ->name('shop.items.create');
        
        Route::post('/', [\App\Http\Controllers\Admin\ShopItemController::class, 'store'])
            ->name('shop.items.store');
        
        Route::get('/{item}/edit', [\App\Http\Controllers\Admin\ShopItemController::class, 'edit'])
            ->name('shop.items.edit');
        
        Route::put('/{item}', [\App\Http\Controllers\Admin\ShopItemController::class, 'update'])
            ->name('shop.items.update');
        
        Route::delete('/{item}', [\App\Http\Controllers\Admin\ShopItemController::class, 'destroy'])
            ->name('shop.items.destroy');
        
        Route::post('/sort', [\App\Http\Controllers\Admin\ShopItemController::class, 'updateSortOrder'])
            ->name('shop.items.sort');
    });

    Route::post('/api/transactions', [\App\Http\Controllers\Admin\TransactionController::class, 'store'])
        ->name('api.transactions.store');
    
    Route::get('/api/transactions/{externalTxId}', [\App\Http\Controllers\Admin\TransactionController::class, 'getByExternalId'])
        ->name('api.transactions.get');
});
