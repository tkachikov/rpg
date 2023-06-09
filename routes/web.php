<?php
declare(strict_types=1);

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use App\Http\Controllers\WindowController;
use App\Http\Controllers\ProfileController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('test', function () {
    return response(app(\App\Game::class)->user(\Illuminate\Support\Facades\Auth::user())->render())->header('Content-Type', 'image/png');
});

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::name('window.')
    ->prefix('window')
    ->middleware('auth')
    ->controller(WindowController::class)
    ->group(function () {
        Route::get('', 'index')->name('index');
    });

Route::name('render.')
    ->prefix('render')
    ->middleware('auth')
    ->controller(WindowController::class)
    ->group(function () {
        Route::get('', 'render')->name('index');
        Route::post('move', 'move')->name('move');
        Route::post('click', 'click')->name('click');
        Route::post('event', 'event')->name('event');
    });

require __DIR__.'/auth.php';
