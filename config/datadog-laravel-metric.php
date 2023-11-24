<?php

// config for Mamitech/DatadogLaravelMetric
return [
    'enabled' => env('DATADOG_METRIC_ENABLED', false),
    'init_config' => [
        'host' => env('DATADOG_STATSD_SERVER', 'localhost'),
        'port' => env('DATADOG_STATSD_PORT', 8125),
        'socket_path' => env('DATADOG_SOCKET_PATH'),
        'datadog_host' => env('DATADOG_HOST', 'https://app.datadoghq.com'),
        'api_key' => env('DATADOG_API_KEY'),
        'app_key' => env('DATADOG_APP_KEY'),
        'global_tags' => [],
        // prefix every metric with this string.
        // end with '.' for better readibility. example: 'laravel.'
        'metric_prefix' => env('DATADOG_METRIC_PREFIX'),
    ],
    'tags' => [
        'app' => env('DATADOG_TAGS_APP') ?? config('app.name'),
        'env' => env('DATADOG_TAGS_ENV') ?? config('app.env'),
    ],
    'middleware' => [
        'metric_name' => env('DATADOG_MIDDLEWARE_METRIC_NAME', 'request'),
        // on middleware metric, exclude certain tags from being sent to datadog.
        // put them in a comma separated string.
        // list of possible tags: app,environment,action,host,status_code
        'exclude_tags' => explode(',', env('DATADOG_MIDDLEWARE_EXCLUDE_TAGS', '')),

        'tag_transformers' => [
            // list of classes that implements Mamitech\DatadogLaravelMetric\TagTransformer
            // example: \App\Datadog\AddHostTagTransformer
        ],
    ],
];
