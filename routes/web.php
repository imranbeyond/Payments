<?php
Route::middleware(['web', 'auth'])->prefix('external-apps/payment-gateways')->group(function () {
    Route::get('/settings', 'Controllers\PaymentGatewayController@index');
    Route::get('/configure/{slug}', 'Controllers\PaymentGatewayController@configure');
    Route::post('/configure/{slug}', 'Controllers\PaymentGatewayController@store');
    Route::post('/toggle/{slug}', 'Controllers\PaymentGatewayController@toggle');
    Route::post('/test/{slug}', 'Controllers\PaymentGatewayController@testConnection');
});
