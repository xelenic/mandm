<?php

use App\Http\Controllers\Admin\PromoExportController;
use App\Http\Controllers\RedeemController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Gift packages: each QR code is unique, /r/{code}.
// Every other package: the same shared QR code, /try-again.
Route::middleware('throttle:redeem')
    ->where(['code' => '[A-Za-z0-9]{1,32}'])
    ->group(function () {
        Route::get('/try-again', [RedeemController::class, 'tryAgain'])->name('redeem.try-again');
        Route::get('/r/{code}', [RedeemController::class, 'show'])->name('redeem.show');
        Route::post('/r/{code}', [RedeemController::class, 'store'])
            ->middleware('throttle:redeem-submit')
            ->name('redeem.store');
    });

// Downloads started from the admin panel (signed, short-lived links; admins only).
Route::middleware('signed')->prefix('admin/exports')->group(function () {
    Route::get('qr-codes', [PromoExportController::class, 'qrCodes'])->name('admin.qr-codes.export');
    Route::get('claims', [PromoExportController::class, 'claims'])->name('admin.claims.export');
});
