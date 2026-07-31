<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
        // Bagikan $siteSettings ke seluruh view secara global
        // agar layout (main.blade.php, app.blade.php, dll.) dapat membaca
        // pengaturan identitas situs yang disimpan di database.
        View::composer('*', function ($view) {
            if (Schema::hasTable('site_settings')) {
                $view->with('siteSettings', DB::table('site_settings')->pluck('value', 'key')->toArray());
            }
        });
    }
}
