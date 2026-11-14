<?php

use Illuminate\Support\Facades\Route;


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

Route::get('/', [\App\Http\Controllers\PageController::class, 'home'])->name('site.home');
Route::get('contact', [\App\Http\Controllers\PageController::class, 'contact'])->name('site.contact');
foreach (['about', 'mission', 'vision', 'privacy', 'terms'] as $pageKey) {
    Route::get($pageKey, [\App\Http\Controllers\PageController::class, 'text'])->defaults('key', $pageKey)->name('site.' . $pageKey);
}
foreach (['business-ideas', 'skills', 'opportunities', 'articles', 'photos', 'videos'] as $typeKey) {
    Route::get($typeKey, [\App\Http\Controllers\SiteContentController::class, 'index'])->defaults('res', $typeKey)->name('site.' . $typeKey . '.index');
    Route::get($typeKey . '/{slug}', [\App\Http\Controllers\SiteContentController::class, 'show'])->defaults('res', $typeKey)->where('slug', '[A-Za-z0-9\-]+')->name('site.' . $typeKey . '.show');
}

Route::prefix('admin')->name('admin.')->middleware(\App\Http\Middleware\EnsureAdmin::class)->group(function () {
    $gallery = \App\Http\Controllers\Admin\GalleryController::class;
    foreach (['photos', 'videos'] as $res) {
        Route::get($res, [$gallery, 'index'])->defaults('res', $res)->name($res . '.index');
        Route::get($res . '/create', [$gallery, 'create'])->defaults('res', $res)->name($res . '.create');
        Route::post($res, [$gallery, 'store'])->defaults('res', $res)->name($res . '.store');
        Route::get($res . '/{id}/edit', [$gallery, 'edit'])->defaults('res', $res)->whereNumber('id')->name($res . '.edit');
        Route::put($res . '/{id}', [$gallery, 'update'])->defaults('res', $res)->whereNumber('id')->name($res . '.update');
        Route::delete($res . '/{id}', [$gallery, 'destroy'])->defaults('res', $res)->whereNumber('id')->name($res . '.destroy');
        Route::post($res . '/{id}/publish', [$gallery, 'publish'])->defaults('res', $res)->whereNumber('id')->name($res . '.publish');
        Route::post($res . '/{id}/feature', [$gallery, 'feature'])->defaults('res', $res)->whereNumber('id')->name($res . '.feature');
    }

    $slides = \App\Http\Controllers\Admin\SlideController::class;
    Route::get('slides', [$slides, 'index'])->name('slides.index');
    Route::get('slides/create', [$slides, 'create'])->name('slides.create');
    Route::post('slides', [$slides, 'store'])->name('slides.store');
    Route::get('slides/{slide}/edit', [$slides, 'edit'])->name('slides.edit');
    Route::put('slides/{slide}', [$slides, 'update'])->name('slides.update');
    Route::delete('slides/{slide}', [$slides, 'destroy'])->name('slides.destroy');
    Route::post('slides/{slide}/toggle', [$slides, 'toggle'])->name('slides.toggle');
    Route::post('slides/{slide}/move/{direction}', [$slides, 'move'])->where('direction', 'up|down')->name('slides.move');

    $settings = \App\Http\Controllers\Admin\SettingsController::class;
    Route::get('settings', [$settings, 'index'])->name('settings.index');
    Route::put('settings', [$settings, 'update'])->name('settings.update');

    $comments = \App\Http\Controllers\Admin\CommentController::class;
    Route::get('comments', [$comments, 'index'])->name('comments.index');
    Route::post('comments/{comment}/status', [$comments, 'status'])->name('comments.status');
    Route::delete('comments/{comment}', [$comments, 'destroy'])->name('comments.destroy');
});

Route::get('engage/{type}/{id}/comments', [\App\Http\Controllers\CommentFeedController::class, 'index'])->whereNumber('id')->middleware('throttle:60,1');

Route::get('search', [\App\Http\Controllers\SearchController::class, 'index'])->name('site.search');
Route::get('search/suggest', [\App\Http\Controllers\SearchController::class, 'suggest'])->middleware('throttle:120,1')->name('site.search.suggest');
Route::get('robots.txt', [\App\Http\Controllers\SeoFileController::class, 'robots']);
Route::get('sitemap.xml', [\App\Http\Controllers\SeoFileController::class, 'sitemap']);

Route::prefix('admin')->name('admin.')->middleware(\App\Http\Middleware\EnsureAdmin::class)->group(function () {
    Route::get('seo', [\App\Http\Controllers\Admin\SeoController::class, 'index'])->name('seo.index');
    Route::put('seo', [\App\Http\Controllers\Admin\SeoController::class, 'update'])->name('seo.update');
});

Route::prefix('admin')->name('admin.')->middleware(\App\Http\Middleware\EnsureAdmin::class)->group(function () {
    Route::get('media', [\App\Http\Controllers\Admin\MediaController::class, 'index'])->name('media.index');
    Route::post('media/link', [\App\Http\Controllers\Admin\MediaController::class, 'link'])->name('media.link');
    Route::delete('media/{medium}', [\App\Http\Controllers\Admin\MediaController::class, 'destroy'])->name('media.destroy');
});

Route::prefix('admin')->name('admin.')->middleware(\App\Http\Middleware\EnsureAdmin::class)->group(function () {
    $links = \App\Http\Controllers\Admin\LinkController::class;
    Route::get('links', [$links, 'index'])->name('links.index');
    Route::get('links/create', [$links, 'create'])->name('links.create');
    Route::post('links', [$links, 'store'])->name('links.store');
    Route::get('links/{link}/edit', [$links, 'edit'])->name('links.edit');
    Route::put('links/{link}', [$links, 'update'])->name('links.update');
    Route::delete('links/{link}', [$links, 'destroy'])->name('links.destroy');

    $profile = \App\Http\Controllers\Admin\ProfileController::class;
    Route::get('profile', [$profile, 'edit'])->name('profile.edit');
    Route::put('profile', [$profile, 'update'])->name('profile.update');
    Route::put('profile/password', [$profile, 'password'])->name('profile.password');
});

Route::prefix('admin')->name('admin.')->middleware(\App\Http\Middleware\EnsureAdmin::class)->group(function () {
    $links = \App\Http\Controllers\Admin\LinkController::class;
    Route::get('links', [$links, 'index'])->name('links.index');
    Route::get('links/create', [$links, 'create'])->name('links.create');
    Route::post('links', [$links, 'store'])->name('links.store');
    Route::get('links/{link}/edit', [$links, 'edit'])->name('links.edit');
    Route::put('links/{link}', [$links, 'update'])->name('links.update');
    Route::delete('links/{link}', [$links, 'destroy'])->name('links.destroy');

    $profile = \App\Http\Controllers\Admin\ProfileController::class;
    Route::get('profile', [$profile, 'edit'])->name('profile.edit');
    Route::put('profile', [$profile, 'update'])->name('profile.update');
    Route::put('profile/password', [$profile, 'password'])->name('profile.password');
});
