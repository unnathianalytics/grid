<?php

use App\Http\Controllers\ResortFormController;
use App\Livewire\ResortsIndex;
use Illuminate\Support\Facades\Route;

Route::get('/', ResortsIndex::class);

Route::get('/resorts', ResortsIndex::class);
Route::get('/booking', App\Livewire\BookingEntry::class);

// LaraForm demo — every field type on one keyboard-first form (no Livewire).
Route::get('/resorts/create', [ResortFormController::class, 'create'])->name('resorts.create');
Route::post('/resorts', [ResortFormController::class, 'store'])->name('resorts.store');
Route::get('/resorts/{resort}/edit', [ResortFormController::class, 'edit'])->name('resorts.edit');
Route::put('/resorts/{resort}', [ResortFormController::class, 'update'])->name('resorts.update');
Route::get('/api/cities', [ResortFormController::class, 'cities'])->name('cities.search');
