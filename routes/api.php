<?php

use App\Http\Controllers\Api\SegmentController;
use App\Http\Controllers\EventLogController;
use Illuminate\Support\Facades\Route;

Route::get('/segments', [SegmentController::class, 'index']);
Route::post('/event-log/track', [EventLogController::class, 'track']);
