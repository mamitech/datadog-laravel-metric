<?php

namespace Mamitech\DatadogLaravelMetric;

use Closure;
use DataDog\DogStatsd;

class DatadogLaravelMetric
{
    private $dogstatsd;

    public function __construct(DogStatsd $dogStatsd)
    {
        $this->dogstatsd = $dogStatsd;
    }

    public static function initFromConfig(): DatadogLaravelMetric
    {
        $config = config('datadog-laravel-metric.init_config');

        return new DatadogLaravelMetric(new DogStatsd($config));
    }

    /**
     * Measure the execution time of a function and send it to Datadog.
     *
     * @param  string  $metricName - The name of the metric to send to Datadog.
     * @param  array  $tags - The tags to send to Datadog.
     * @param  Closure  $func - The function to execute.
     * @param  int  $sampling - The sampling rate to send to Datadog.
     */
    public function measureFunc(string $metricName, array $tags, Closure $func, int $sampling = 1)
    {
        $startTime = microtime(true);

        $returnVal = $func();

        if (! config('datadog-laravel-metric.enabled', false)) {
            return $returnVal;
        }

        $duration = microtime(true) - $startTime;

        try {
            $this->dogstatsd->microtiming($metricName, $duration, $sampling, $tags);
        } catch (\Throwable $th) {
            if (class_exists('Illuminate\Support\Facades\Log')) {
                \Illuminate\Support\Facades\Log::warning('DatadogLaravelMetric: ' . $th->getMessage());
            }
        }

        return $returnVal;
    }

    /**
     * Send metric data to Datadog. You need to implement the duration counter yourself.
     * Example:
     *      $startTime = microtime(true);
     *      // Do something
     *      $duration = microtime(true) - $startTime;
     *      $tags = ['tag1' => 'value1', 'tag2' => 'value2'];
     *      $datadog->measure('my.metric', $duration, $tags);
     *
     * This function is better if you need the tags based on result of that doing something.
     * Example: tag the success value of a function call.
     *
     * @param  string  $metricName - The name of the metric to send to Datadog.
     * @param  float  $duration - The duration to send to Datadog.
     * @param  array  $tags - The tags to send to Datadog.
     * @param  int  $sampling - The sampling rate to send to Datadog.
     */
    public function measure(string $metricName, array $tags, float $duration, int $sampling = 1)
    {
        if (! config('datadog-laravel-metric.enabled', false)) {
            return;
        }
        try {
            $this->dogstatsd->microtiming($metricName, $duration, $sampling, $tags);
        } catch (\Throwable $th) {
            if (class_exists('Illuminate\Support\Facades\Log')) {
                \Illuminate\Support\Facades\Log::warning('DatadogLaravelMetric: ' . $th->getMessage());
            }
        }
    }

    /**
     * Handle dynamic function calls to be forwarded to DogstatsD.
     * Mainly for main function of DogstatsD : increment, decrement, gauge, set, histogram, distribution
     * Wrapped with toggle.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (! config('datadog-laravel-metric.enabled', false)) {
            return;
        }
        try {
            $this->dogstatsd->$method(...$parameters);
        } catch (\Throwable $th) {
            if (class_exists('Illuminate\Support\Facades\Log')) {
                \Illuminate\Support\Facades\Log::warning('DatadogLaravelMetric: ' . $th->getMessage());
            }
        }
    }
}
