<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\ResortFormController;
use App\Http\Controllers\ThemesController;
use App\Livewire\BookingEntry;
use App\Livewire\JournalEntry;
use App\Livewire\ResortsIndex;
use App\Support\Seo;
use Illuminate\Support\Facades\Route;

// The overview / landing page — the keyword-bearing entry point for the whole demo.
Route::get('/', [HomeController::class, 'index'])->name('home');

// LaraGrid demos, one per mode.
Route::get('/resorts', ResortsIndex::class)->name('resorts.index');   // readonly, server-side
Route::get('/booking', BookingEntry::class)->name('booking');          // editable entry grid
Route::get('/journal', JournalEntry::class)->name('journal');          // editable, double-entry
Route::get('/reports', [ReportsController::class, 'index'])->name('reports');  // display-only, no Livewire
Route::get('/themes', [ThemesController::class, 'index'])->name('themes');     // theming & density

// LaraForm demo — every field type on one keyboard-first form (no Livewire).
Route::get('/resorts/create', [ResortFormController::class, 'create'])->name('resorts.create');
Route::post('/resorts', [ResortFormController::class, 'store'])->name('resorts.store');
Route::get('/resorts/{resort}/edit', [ResortFormController::class, 'edit'])->name('resorts.edit');
Route::put('/resorts/{resort}', [ResortFormController::class, 'update'])->name('resorts.update');
Route::get('/api/cities', [ResortFormController::class, 'cities'])->name('cities.search');

// SEO plumbing: a generated sitemap and a robots.txt that points at it. Kept as routes (not
// static files under public/) so both follow APP_URL across local, staging and production.
Route::get('/sitemap.xml', function () {
    return response()
        ->view('sitemap', ['pages' => Seo::sitemap(), 'lastmod' => now()->toAtomString()])
        ->header('Content-Type', 'application/xml');
})->name('sitemap');

Route::get('/robots.txt', function () {
    return response(
        "User-agent: *\nAllow: /\n\nSitemap: ".url('/sitemap.xml')."\n"
    )->header('Content-Type', 'text/plain');
})->name('robots');
