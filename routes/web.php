<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\ConnectorController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WebController;
use Illuminate\Support\Facades\Route;

Route::get('/', [WebController::class, 'home'])->name('home');
Route::get('/calendar', [WebController::class, 'calendar'])->name('calendar');
Route::get('/map', [WebController::class, 'map'])->name('map');
Route::get('/events/{event}', [WebController::class, 'show'])->name('events.show');
Route::get('/events/{event}/calendar.ics', [WebController::class, 'calendarDownload'])->name('events.calendar');
Route::get('/saved', [WebController::class, 'saved'])->middleware('auth')->name('saved');

Route::get('/dashboard', function () {
    return redirect()->route('saved');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/admin', function () {
    return view('admin');
})->middleware(['auth', 'admin'])->name('admin');

// Admin API routes (under web middleware for session support)
Route::middleware(['auth', 'admin'])->prefix('api/admin')->group(function () {
    Route::get('stats', [AdminController::class, 'stats']);
    Route::get('events', [AdminController::class, 'events']);
    Route::post('events', [AdminController::class, 'storeEvent']);
    Route::put('events/{event}', [AdminController::class, 'updateEvent']);
    Route::delete('events/{event}', [AdminController::class, 'deleteEvent']);
    Route::put('events/{event}/status', [AdminController::class, 'updateEventStatus']);

    Route::get('categories', [AdminController::class, 'categories']);
    Route::post('categories', [AdminController::class, 'storeCategory']);
    Route::put('categories/{category}', [AdminController::class, 'updateCategory']);
    Route::delete('categories/{category}', [AdminController::class, 'deleteCategory']);

    Route::get('users', [AdminController::class, 'users']);
    Route::patch('users/{user}', [AdminController::class, 'updateUser']);
    Route::delete('users/{user}', [AdminController::class, 'deleteUser']);

    Route::get('settings', [AdminController::class, 'settings']);
    Route::put('settings', [AdminController::class, 'updateSettings']);

    Route::get('logs', [AdminController::class, 'logs']);
    Route::delete('logs', [AdminController::class, 'clearLogs']);
});

Route::middleware('auth')->group(function () {
    Route::get('/connector', [ConnectorController::class, 'index'])->name('connector.index');
    Route::post('/connector/download', [ConnectorController::class, 'download'])
        ->middleware('throttle:5,1')
        ->name('connector.download');
    Route::delete('/connector/tokens/{token}', [ConnectorController::class, 'revoke'])->name('connector.tokens.revoke');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
