<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('engage/{type}/{id}')->whereNumber('id')->group(function () {
    Route::post('like', [\App\Http\Controllers\EngagementController::class, 'like'])->middleware('throttle:30,1');
    Route::post('view', [\App\Http\Controllers\EngagementController::class, 'view'])->middleware('throttle:60,1');
    Route::post('comment', [\App\Http\Controllers\EngagementController::class, 'comment'])->middleware('throttle:5,1');
});
