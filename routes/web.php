<?php

use App\Livewire\ResortsIndex;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/resorts', ResortsIndex::class);
