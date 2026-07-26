<?php

namespace App\Providers;

use App\Services\Contracts\FileTextExtractorInterface;
use App\Services\FileExtraction\PdfTextExtractor;
use App\Services\FileExtraction\TextNormalizerService;
use App\Services\FileExtraction\WordTextExtractor;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TextNormalizerService::class, function ($app) {
            return new TextNormalizerService([
                $app->make(PdfTextExtractor::class),
                $app->make(WordTextExtractor::class),
            ]);
        });

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();
    }
}
