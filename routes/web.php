<?php

use App\Livewire\ResortsIndex;
use Illuminate\Support\Facades\Route;

Route::get('/', ResortsIndex::class);

Route::get('/resorts', ResortsIndex::class);
Route::get('/booking', App\Livewire\BookingEntry::class);
