<?php

namespace Mamitech\DatadogLaravelMetric;

use Closure;
use DataDog\DogStatsd;

class DatadogLaravelMetric extends DogStatsd
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

        $this->dogstatsd->microtiming($metricName, $duration, $sampling, $tags);

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
        $this->dogstatsd->microtiming($metricName, $duration, $sampling, $tags);
    }
}
