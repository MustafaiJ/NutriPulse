<?php

namespace App\Providers;

use App\Contracts\AiProvider;
use App\Services\AiProviders\GeminiAiProvider;
use App\Services\AiProviders\HeuristicAiProvider;
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
    }
}