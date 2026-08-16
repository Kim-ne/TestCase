<?php

namespace App\Providers;

use App\Repositories\Contracts\TestGenerationRequestRepositoryInterface;
use App\Repositories\TestGenerationRequestRepository;
use App\Services\Contracts\TestCaseGeneratorServiceInterface;
use App\Services\FileExtraction\PdfTextExtractor;
use App\Services\FileExtraction\TextNormalizerService;
use App\Services\FileExtraction\TxtExtractor;
use App\Services\FileExtraction\WordTextExtractor;
use App\Services\TestCaseGeneratorService;
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
                $app->make(TxtExtractor::class),
            ]);
        });

        $this->app->bind(TestCaseGeneratorServiceInterface::class, TestCaseGeneratorService::class);

        $this->app->bind(TestGenerationRequestRepositoryInterface::class, TestGenerationRequestRepository::class);

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();
    }
}
