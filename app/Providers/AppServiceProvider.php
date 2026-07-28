<?php

namespace App\Providers;

use App\Grid\Casting\StarsCast;
use App\Grid\Formatting\InrFormatter;
use Illuminate\Support\ServiceProvider;
use LaraGrid\Casting\CastRegistry;
use LaraGrid\Formatting\FormatRegistry;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // LaraGrid's PHP-side extension seams. Both registries are singletons the package
        // resolves whenever it needs to render or cast a value server-side; each entry here
        // has a behaviourally identical JavaScript twin registered through
        // window.LaraGrid.pending (see resources/views/reports/index.blade.php).
        $this->app->make(FormatRegistry::class)->register('inr', new InrFormatter);
        $this->app->make(CastRegistry::class)->register('stars', new StarsCast);
    }
}
