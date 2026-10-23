<?php
use App\Http\Controllers\Controller;
use App\Http\Controllers\OidcController;
use App\Http\Middleware\VerifyOidcCsrfToken;

Route::prefix('auth/oidc')->middleware(VerifyOidcCsrfToken::class)->group(static function () {
    Route::get('config', [OidcController::class, 'configuration']);
    Route::get('redirect', [OidcController::class, 'redirect'])->middleware('throttle:20,1')->block(90, 10);
    Route::get('callback', [OidcController::class, 'callback'])->middleware('throttle:20,1')->block(90, 10);
    Route::post('token', [OidcController::class, 'token'])->middleware('throttle:20,1')->block(90, 10);
});

Route::fallback([Controller::class, 'frontendRoute']);
