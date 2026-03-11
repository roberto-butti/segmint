<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', App\Http\Controllers\DashboardController::class)->name('dashboard');
    Route::get('projects', [App\Http\Controllers\ProjectController::class, 'index'])->name('projects.index');
    Route::get('projects/{project:slug}', [App\Http\Controllers\ProjectController::class, 'show'])->name('projects.show');
    Route::get('projects/{project:slug}/edit', [App\Http\Controllers\ProjectController::class, 'edit'])->name('projects.edit');
    Route::put('projects/{project:slug}', [App\Http\Controllers\ProjectController::class, 'update'])->name('projects.update');
    Route::get('projects/{project:slug}/segments', [App\Http\Controllers\SegmentController::class, 'index'])->name('projects.segments.index');
    Route::get('projects/{project:slug}/access-tokens', [App\Http\Controllers\AccessTokenController::class, 'index'])->name('projects.access-tokens.index');
});

require __DIR__.'/settings.php';
