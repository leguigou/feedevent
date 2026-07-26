<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WebController;
use Illuminate\Support\Facades\Route;

Route::get('/', [WebController::class, 'home'])->name('home');
Route::get('/calendar', [WebController::class, 'calendar'])->name('calendar');
Route::get('/map', [WebController::class, 'map'])->name('map');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/admin', function () {
    return view('admin');
})->middleware(['auth'])->name('admin');

// Admin API routes (under web middleware for session support)
Route::middleware(['auth', 'admin'])->prefix('api/admin')->group(function () {
    Route::get('stats', [\App\Http\Controllers\Api\AdminController::class, 'stats']);
    Route::get('events', [\App\Http\Controllers\Api\AdminController::class, 'events']);
    Route::post('events', [\App\Http\Controllers\Api\AdminController::class, 'storeEvent']);
    Route::put('events/{event}', [\App\Http\Controllers\Api\AdminController::class, 'updateEvent']);
    Route::delete('events/{event}', [\App\Http\Controllers\Api\AdminController::class, 'deleteEvent']);
    Route::put('events/{event}/status', [\App\Http\Controllers\Api\AdminController::class, 'updateEventStatus']);

    Route::get('categories', [\App\Http\Controllers\Api\AdminController::class, 'categories']);
    Route::post('categories', [\App\Http\Controllers\Api\AdminController::class, 'storeCategory']);
    Route::put('categories/{category}', [\App\Http\Controllers\Api\AdminController::class, 'updateCategory']);
    Route::delete('categories/{category}', [\App\Http\Controllers\Api\AdminController::class, 'deleteCategory']);

    Route::get('users', [\App\Http\Controllers\Api\AdminController::class, 'users']);

    Route::get('logs', [\App\Http\Controllers\Api\AdminController::class, 'logs']);
    Route::delete('logs', [\App\Http\Controllers\Api\AdminController::class, 'clearLogs']);
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
