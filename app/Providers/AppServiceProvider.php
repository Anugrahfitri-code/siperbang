<?php

namespace App\Providers;

use App\Services\SiteBrandingService;
use App\Support\SiteBranding\HtmlSanitizer;
use App\Support\SiteBranding\ImageOptimizer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        Builder::macro('whereLikePortable', function (string $column, string $search) {
            $columnWrapped = $this->getGrammar()->wrap($column);
            $escapedSearch = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search);

            return $this->whereRaw("LOWER({$columnWrapped}) LIKE LOWER(?) ESCAPE '!'", ["%{$escapedSearch}%"]);
        });

        Builder::macro('orWhereLikePortable', function (string $column, string $search) {
            $columnWrapped = $this->getGrammar()->wrap($column);
            $escapedSearch = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search);

            return $this->orWhereRaw("LOWER({$columnWrapped}) LIKE LOWER(?) ESCAPE '!'", ["%{$escapedSearch}%"]);
        });
        RateLimiter::for('receipt-ocr', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(10)->by($request->user()->id)
                : Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('stock-upload', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(20)->by($request->user()->id)
                : Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('stock-import', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(15)->by($request->user()->id)
                : Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('pdf-export', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(10)->by($request->user()->id)
                : Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('excel-export', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(20)->by($request->user()->id)
                : Limit::perMinute(5)->by($request->ip());
        });

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
