<?php

namespace Mamitech\DatadogLaravelMetric\Middleware;

use Closure;
use Illuminate\Http\Request;
use Mamitech\DatadogLaravelMetric\DatadogLaravelMetric;

class SendRequestDatadogMetric
{
    private $datadogLaravelMetric;

    public function __construct(DatadogLaravelMetric $datadogLaravelMetric)
    {
        $this->datadogLaravelMetric = $datadogLaravelMetric;
    }

    /**
     * Handle an incoming request and measure request time and send to Datadog.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // measure time
        $metricStartTime = microtime(true);
        $response = $next($request);
        $duration = microtime(true) - $metricStartTime;

        // tags get request controller name, action, and request method and status code
        $action = $request->route()?->getAction()['controller'] ?? 'unknownController@unknownMethod';
        $tags = [
            'app' => config('datadog-laravel-metric.tags.app'),
            'environment' => config('datadog-laravel-metric.tags.env'),
            'action' => $action,
            'domain' => $request->getHost(),
            'status_code' => $response?->getStatusCode() ?? 500,
        ];

        // exclude certain tags from being sent to datadog
        $excludeTags = config('datadog-laravel-metric.middleware.exclude_tags');
        foreach ($excludeTags as $excludeTag) {
            unset($tags[$excludeTag]);
        }

        $tagTransformers = config('datadog-laravel-metric.middleware.tag_transformer');
        // check if $tagTransformers is an array
        if (is_array($tagTransformers)) {
            foreach ($tagTransformers as $transClass) {
                $transformer = app($transClass);
                $tags = $transformer->transform($tags);
            }
        }

        // send to Datadog
        $metricName = config('datadog-laravel-metric.middleware.metric_name');
        $this->datadogLaravelMetric->measure($metricName, $tags, $duration);

        return $response;
    }
}
