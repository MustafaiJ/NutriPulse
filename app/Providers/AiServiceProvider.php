<?php

namespace App\Providers;

use App\Contracts\AiProvider;
use App\Services\AiProviders\GeminiAiProvider;
use App\Services\AiProviders\HeuristicAiProvider;
use App\Services\InsightGenerator;
use App\Services\InsightService;
use Illuminate\Support\ServiceProvider;

class AiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AiProvider::class, function (): AiProvider {
            if (filled(config('services.ai.api_key')) && config('services.ai.provider') === 'gemini') {
                return new GeminiAiProvider(
                    apiKey: config('services.ai.api_key'),
                    model: config('services.ai.model_estimate', 'gemini-2.5-flash'),
                );
            }

            return new HeuristicAiProvider;
        });

        $this->app->singleton(InsightGenerator::class, function (): InsightGenerator {
            return new InsightGenerator(
                apiKey: config('services.ai.api_key') && config('services.ai.provider') === 'gemini'
                    ? config('services.ai.api_key')
                    : null,
                model: config('services.ai.model_insight', 'gemini-2.5-flash'),
            );
        });

        $this->app->singleton(InsightService::class);
    }
}
