<?php

use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\EventImportController;
use Illuminate\Support\Facades\Route;

// Event import & LLM parsing (must be before the wildcard {event} route)
Route::post('events/import', [EventImportController::class, 'import'])->middleware('auth');
Route::get('events/parse-preview', [EventImportController::class, 'parsePreview'])->middleware('auth');

Route::get('events', [EventController::class, 'index']);
Route::get('events/{event}', [EventController::class, 'show']);
Route::post('events', [EventController::class, 'store'])->middleware('auth');

Route::post('events/{event}/like', [EventController::class, 'like'])->middleware('auth');
Route::post('events/{event}/dislike', [EventController::class, 'dislike'])->middleware('auth');
Route::delete('events/{event}/preference', [EventController::class, 'removePreference'])->middleware('auth');

Route::get('recommendations', [EventController::class, 'recommendations'])->middleware('auth');
