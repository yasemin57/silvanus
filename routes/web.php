<?php

declare(strict_types=1);

use App\Http\Controllers\Web\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/incidents', [DashboardController::class, 'incidents'])->name('incidents.index');
Route::get('/incidents/{id}', [DashboardController::class, 'showIncident'])->name('incidents.show');
Route::get('/simulator', [DashboardController::class, 'simulator'])->name('simulator');
