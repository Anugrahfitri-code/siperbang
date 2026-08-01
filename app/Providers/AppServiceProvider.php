<?php

namespace App\Providers;

use App\Services\SiteBrandingService;
use App\Support\SiteBranding\HtmlSanitizer;
use App\Support\SiteBranding\ImageOptimizer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(HtmlSanitizer::class);
        $this->app->singleton(ImageOptimizer::class);
        $this->app->scoped(SiteBrandingService::class);
    }

    public function boot(): void
    {
        View::composer('*', function ($view): void {
            try {
                $view->with(
                    'siteSettings',
                    app(SiteBrandingService::class)->forViews(),
                );
            } catch (Throwable $exception) {
                report($exception);

                $defaults = config('site_branding.defaults', []);
                $view->with('siteSettings', [
                    ...$defaults,
                    'app_logo_url' => $defaults['app_logo_path'] ?? '/images/brand/siperbang-symbol.png',
                    'instansi_logo_url' => $defaults['instansi_logo_path'] ?? '/images/brand/komdigi-logo.png',
                    'favicon_url' => $defaults['favicon_path'] ?? '/images/brand/siperbang-symbol.png',
                    'footer_copyright_rendered' => str_replace(
                        ['{year}', '{app_name}', '{instansi_name}', '{instansi_full_name}'],
                        [
                            now()->year,
                            $defaults['app_name'] ?? '',
                            $defaults['instansi_name'] ?? '',
                            $defaults['instansi_sub'] ?? '',
                        ],
                        $defaults['footer_copyright'] ?? '',
                    ),
                ]);
            }
        });
    }
}
