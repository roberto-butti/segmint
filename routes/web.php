<?php

use App\Http\Controllers\AccessTokenController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SegmentController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('projects/{project:slug}', [ProjectController::class, 'show'])->name('projects.show');
    Route::get('projects/{project:slug}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
    Route::put('projects/{project:slug}', [ProjectController::class, 'update'])->name('projects.update');
    Route::get('projects/{project:slug}/segments', [SegmentController::class, 'index'])->name('projects.segments.index');
    Route::get('projects/{project:slug}/access-tokens', [AccessTokenController::class, 'index'])->name('projects.access-tokens.index');
});

require __DIR__.'/settings.php';
