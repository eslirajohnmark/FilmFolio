<?php

use App\Http\Controllers\Admin\AccountController as AdminAccountController;
use App\Http\Controllers\Admin\Auth\AdminLoginController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\FilmController as AdminFilmController;
use App\Http\Controllers\Admin\ListController as AdminListController;
use App\Http\Controllers\Admin\ModerationController as AdminModerationController;
use App\Http\Controllers\BrowserSessionController;
use App\Http\Controllers\DiaryController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\FilmController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ListController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\TeaserController;
use App\Http\Controllers\WatchlistController;
use App\Models\Admin;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/search', [SearchController::class, 'index'])->middleware('throttle:search')->name('search');

Route::get('/films', [FilmController::class, 'index'])->name('films.index');
Route::get('/films/tmdb/{tmdbId}', [SearchController::class, 'importFromTmdb'])
    ->whereNumber('tmdbId')->middleware('throttle:20,1')->name('films.tmdb');
Route::get('/films/{film}', [FilmController::class, 'show'])->name('films.show');
Route::post('/films/{film}/reviews', [FilmController::class, 'storeReview'])
    ->middleware(['auth', 'throttle:writes'])->name('films.review');

Route::get('/reviews/{review}', [ReviewController::class, 'show'])->name('reviews.show');
Route::middleware(['auth', 'throttle:writes'])->group(function () {
    Route::post('/reviews/{review}/comments', [ReviewController::class, 'storeComment'])
        ->middleware('throttle:10,1')->name('reviews.comments.store');
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');
    Route::delete('/comments/{comment}', [ReviewController::class, 'destroyComment'])->name('comments.destroy');
});

Route::get('/lists', [ListController::class, 'index'])->name('lists.index');
Route::get('/lists/{list}', [ListController::class, 'show'])->name('lists.show');

Route::middleware(['auth', 'throttle:writes'])->group(function () {
    Route::post('/lists', [ListController::class, 'store'])->name('lists.store');
    Route::patch('/lists/{list}', [ListController::class, 'update'])->name('lists.update');
    Route::delete('/lists/{list}', [ListController::class, 'destroy'])->name('lists.destroy');
    Route::delete('/lists/{list}/films/{film}', [ListController::class, 'removeFilm'])->name('lists.films.destroy');
});

Route::get('/members', [MemberController::class, 'index'])->name('members.index');
Route::get('/teasers', [TeaserController::class, 'index'])->name('teasers.index');

Route::get('/tags/{tag:slug}', [TagController::class, 'show'])->name('tags.show');
Route::get('/profile/{user}', [MemberController::class, 'show'])->name('profile.show');

Route::middleware('auth')->group(function () {
    Route::get('/feed', [FeedController::class, 'index'])->name('feed.index');
    Route::get('/diary', [DiaryController::class, 'index'])->name('diary.index');
    Route::get('/watchlist', [WatchlistController::class, 'index'])->name('watchlist.index');
    Route::get('/settings', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/settings', [ProfileController::class, 'update'])->middleware('throttle:writes')->name('profile.update');
    Route::delete('/settings', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::patch('/settings/notifications', [ProfileController::class, 'updateNotifications'])
        ->middleware('throttle:writes')->name('profile.notifications');

    Route::delete('/settings/sessions', [BrowserSessionController::class, 'destroyOthers'])
        ->middleware('throttle:6,1')->name('sessions.destroy-others');
    Route::delete('/settings/sessions/{session}', [BrowserSessionController::class, 'destroy'])
        ->middleware('throttle:writes')->name('sessions.destroy');
});

Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::post('/films/import', [AdminFilmController::class, 'import'])->name('films.import');
    Route::patch('/films/{film}/teaser', [AdminFilmController::class, 'toggleTeaser'])->name('films.teaser');
    Route::resource('films', AdminFilmController::class)->except('show');

    Route::get('/lists', [AdminListController::class, 'index'])->name('lists.index');
    Route::get('/lists/create', [AdminListController::class, 'create'])->name('lists.create');
    Route::post('/lists', [AdminListController::class, 'store'])->name('lists.store');
    Route::patch('/lists/{list}/feature', [AdminListController::class, 'toggleFeatured'])->name('lists.feature');
    Route::delete('/lists/{list}', [AdminListController::class, 'destroy'])->name('lists.destroy');

    Route::get('/accounts', [AdminAccountController::class, 'index'])->name('accounts.index');
    Route::get('/accounts/{user}', [AdminAccountController::class, 'show'])->name('accounts.show');
    Route::post('/accounts/{user}/suspend', [AdminAccountController::class, 'suspend'])->name('accounts.suspend');
    Route::post('/accounts/{user}/ban', [AdminAccountController::class, 'ban'])->name('accounts.ban');
    Route::post('/accounts/{user}/reinstate', [AdminAccountController::class, 'reinstate'])->name('accounts.reinstate');
    Route::patch('/accounts/{user}/feature', [AdminAccountController::class, 'toggleFeatured'])->name('accounts.feature');
    Route::delete('/accounts/{user}', [AdminAccountController::class, 'destroy'])->name('accounts.destroy');
    Route::delete('/accounts/{user}/sessions', [AdminAccountController::class, 'signOutEverywhere'])->name('accounts.sessions.destroy');

    Route::delete('/reviews/{review}', [AdminModerationController::class, 'destroyReview'])->name('reviews.destroy');
    Route::delete('/comments/{comment}', [AdminModerationController::class, 'destroyComment'])->name('comments.destroy');

    Route::post('/logout', [AdminLoginController::class, 'logout'])->name('logout');
});

Route::get('/admin/login', [AdminLoginController::class, 'show'])->name('admin.login');
Route::post('/admin/login', [AdminLoginController::class, 'login'])->middleware('throttle:login')->name('admin.login.submit');

require __DIR__.'/auth.php';

// ⚠️ DEV ONLY — remove before production!
if (app()->environment('local')) {
    Route::get('/__dev-login', function () {
        $admin = Admin::first();
        if (! $admin) {
            abort(404, 'No admin exists. Run: php artisan db:seed --class=AdminSeeder');
        }
        auth('admin')->login($admin);

        return redirect()->route('admin.dashboard');
    })->name('dev.login');
}
