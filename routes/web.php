<?php

use App\Http\Controllers\Admin\PromoExportController;
use App\Http\Controllers\LuckyDrawController;
use Illuminate\Support\Facades\Route;

/*
| The lucky draw screens (design: public/m&m.pdf). Printed QR codes point to
| /r/{code} (gift cards) and /try-again (every card without a gift), so those
| two addresses must never change.
*/
Route::middleware('throttle:redeem')
    ->where(['code' => '[A-Za-z0-9]{1,32}'])
    ->controller(LuckyDrawController::class)
    ->group(function () {
        // No card scanned: the menu, then type the code printed on the card.
        Route::get('/', 'home')->name('home');
        Route::get('/draw', 'enterCode')->name('lucky.enter-code');
        Route::post('/draw', 'search')->middleware('throttle:code-search')->name('redeem.search');

        // Menu information pages.
        Route::get('/campaign', 'campaign')->name('lucky.campaign');
        Route::get('/terms', 'terms')->name('lucky.terms');
        Route::get('/winners', 'winners')->name('lucky.winners');

        // The shared try-again card.
        Route::get('/try-again', 'tryAgainMenu')->name('redeem.try-again');
        Route::get('/try-again/draw', 'tryAgainDraw')->name('lucky.try-again.draw');
        Route::get('/try-again/result', 'tryAgainResult')->name('lucky.try-again.result');

        // Gift cards.
        Route::get('/r/{code}', 'giftMenu')->name('redeem.show');
        Route::get('/r/{code}/draw', 'giftDraw')->name('lucky.draw');
        Route::get('/r/{code}/result', 'giftResult')->name('lucky.result');
        Route::get('/r/{code}/details', 'details')->name('lucky.details');
        Route::post('/r/{code}/details', 'saveDetails')->middleware('throttle:redeem-submit')->name('lucky.details.save');
        Route::get('/r/{code}/review', 'review')->name('lucky.review');
        Route::post('/r/{code}', 'store')->middleware('throttle:redeem-submit')->name('redeem.store');
        Route::get('/r/{code}/received', 'received')->name('lucky.received');
    });

// Downloads started from the admin panel (signed, short-lived links; admins only).
Route::middleware('signed')->prefix('admin/exports')->group(function () {
    Route::get('qr-codes', [PromoExportController::class, 'qrCodes'])->name('admin.qr-codes.export');
    Route::get('claims', [PromoExportController::class, 'claims'])->name('admin.claims.export');
});
