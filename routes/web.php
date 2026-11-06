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

Route::get("admin", [\App\Http\Controllers\Admin\AuthController::class, "showLogin"])->name("admin.login");
Route::post("admin", [\App\Http\Controllers\Admin\AuthController::class, "login"])->middleware("throttle:20,1")->name("admin.login.submit");
Route::prefix("admin")->name("admin.")->middleware(\App\Http\Middleware\EnsureAdmin::class)->group(function () {
    Route::post("logout", [\App\Http\Controllers\Admin\AuthController::class, "logout"])->name("logout");
    Route::get("dashboard", [\App\Http\Controllers\Admin\DashboardController::class, "index"])->name("dashboard");
});

Route::prefix('admin')->name('admin.')->middleware(\App\Http\Middleware\EnsureAdmin::class)->group(function () {
    $content = \App\Http\Controllers\Admin\ContentController::class;
    foreach (array_keys(config('cms')) as $res) {
        Route::get($res, [$content, 'index'])->defaults('res', $res)->name($res . '.index');
        Route::get($res . '/create', [$content, 'create'])->defaults('res', $res)->name($res . '.create');
        Route::post($res, [$content, 'store'])->defaults('res', $res)->name($res . '.store');
        Route::get($res . '/{id}/edit', [$content, 'edit'])->defaults('res', $res)->whereNumber('id')->name($res . '.edit');
        Route::put($res . '/{id}', [$content, 'update'])->defaults('res', $res)->whereNumber('id')->name($res . '.update');
        Route::delete($res . '/{id}', [$content, 'destroy'])->defaults('res', $res)->whereNumber('id')->name($res . '.destroy');
        Route::post($res . '/{id}/publish', [$content, 'publish'])->defaults('res', $res)->whereNumber('id')->name($res . '.publish');
        Route::post($res . '/{id}/feature', [$content, 'feature'])->defaults('res', $res)->whereNumber('id')->name($res . '.feature');
    }

    $cat = \App\Http\Controllers\Admin\CategoryController::class;
    Route::get('categories', [$cat, 'index'])->name('categories.index');
    Route::post('categories', [$cat, 'store'])->name('categories.store');
    Route::get('categories/{category}/edit', [$cat, 'edit'])->name('categories.edit');
    Route::put('categories/{category}', [$cat, 'update'])->name('categories.update');
    Route::delete('categories/{category}', [$cat, 'destroy'])->name('categories.destroy');
});
