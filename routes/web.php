<?php

use App\Http\Controllers\AccessTokenController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventLogViewController;
use App\Http\Controllers\FavoriteProjectController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\RuleTemplateController;
use App\Http\Controllers\SegmentController;
use App\Http\Controllers\SegmentSuggestionController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('organizations/{organization}/dashboard', [DashboardController::class, 'show'])->name('organizations.dashboard');
    Route::get('projects', [ProjectController::class, 'redirectIndex'])->name('projects.index');
    Route::get('organizations/{organization}/projects', [ProjectController::class, 'index'])->name('organizations.projects.index');
    Route::get('organizations/{organization}/projects/create', [ProjectController::class, 'create'])->name('organizations.projects.create');
    Route::post('organizations/{organization}/projects', [ProjectController::class, 'store'])->name('organizations.projects.store');
    Route::get('projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::get('projects/{project}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
    Route::put('projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::post('projects/{project}/favorite', [FavoriteProjectController::class, 'store'])->name('projects.favorite.store');
    Route::delete('projects/{project}/favorite', [FavoriteProjectController::class, 'destroy'])->name('projects.favorite.destroy');
    Route::get('projects/{project}/segments', [SegmentController::class, 'index'])->name('projects.segments.index');
    Route::get('projects/{project}/segments/suggestions', [SegmentSuggestionController::class, 'index'])->name('projects.segments.suggestions');
    Route::get('projects/{project}/segments/create', [SegmentController::class, 'create'])->name('projects.segments.create');
    Route::post('projects/{project}/segments', [SegmentController::class, 'store'])->name('projects.segments.store');
    Route::post('projects/{project}/segments/copy', [SegmentController::class, 'copy'])->name('projects.segments.copy');
    Route::get('projects/{project}/segments/{segment}', [SegmentController::class, 'show'])->name('projects.segments.show');
    Route::get('projects/{project}/segments/{segment}/edit', [SegmentController::class, 'edit'])->name('projects.segments.edit');
    Route::put('projects/{project}/segments/{segment}', [SegmentController::class, 'update'])->name('projects.segments.update');
    Route::post('projects/{project}/segments/{segment}/duplicate', [SegmentController::class, 'duplicate'])->name('projects.segments.duplicate');
    Route::delete('projects/{project}/segments/{segment}', [SegmentController::class, 'destroy'])->name('projects.segments.destroy');
    Route::get('projects/{project}/rule-templates', [RuleTemplateController::class, 'index'])->name('projects.rule-templates.index');
    Route::post('projects/{project}/rule-templates', [RuleTemplateController::class, 'store'])->name('projects.rule-templates.store');
    Route::post('projects/{project}/rule-templates/copy', [RuleTemplateController::class, 'copy'])->name('projects.rule-templates.copy');
    Route::put('projects/{project}/rule-templates/{ruleTemplate}', [RuleTemplateController::class, 'update'])->name('projects.rule-templates.update');
    Route::delete('projects/{project}/rule-templates/{ruleTemplate}', [RuleTemplateController::class, 'destroy'])->name('projects.rule-templates.destroy');
    Route::get('projects/{project}/events', [EventLogViewController::class, 'index'])->name('projects.events.index');
    Route::get('projects/{project}/access-tokens', [AccessTokenController::class, 'index'])->name('projects.access-tokens.index');
    Route::post('projects/{project}/access-tokens', [AccessTokenController::class, 'store'])->name('projects.access-tokens.store');
    Route::patch('projects/{project}/access-tokens/{accessToken}', [AccessTokenController::class, 'update'])->name('projects.access-tokens.update');
    Route::post('projects/{project}/access-tokens/{accessToken}/rotate', [AccessTokenController::class, 'rotate'])->name('projects.access-tokens.rotate');
});

require __DIR__.'/settings.php';
