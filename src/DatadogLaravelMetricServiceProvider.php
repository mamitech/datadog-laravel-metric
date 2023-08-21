<?php

namespace Mamitech\DatadogLaravelMetric;

use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\ServiceProvider;
use Mamitech\DatadogLaravelMetric\Middleware\SendRequestDatadogMetric;

class DatadogLaravelMetricServiceProvider extends ServiceProvider
{
    public function boot(Container $application): void
    {
        $this->publishes([
            __DIR__.'/../config/datadog-laravel-metric.php' => config_path('datadog-laravel-metric.php'),
        ]);

        $kernel = $application->make(Kernel::class);
        $kernel->prependMiddleware(SendRequestDatadogMetric::class);
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/datadog-laravel-metric.php', 'datadog-laravel-metric'
        );

        $this->app->singleton(DatadogLaravelMetric::class, function () {
            return DatadogLaravelMetric::initFromConfig();
        });
    }
}
