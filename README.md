# datadog-laravel-metric

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mamitech/datadog-laravel-metric.svg?style=flat-square)](https://packagist.org/packages/mamitech/datadog-laravel-metric)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/mamitech/datadog-laravel-metric/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/mamitech/datadog-laravel-metric/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/mamitech/datadog-laravel-metric/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/mamitech/datadog-laravel-metric/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/mamitech/datadog-laravel-metric.svg?style=flat-square)](https://packagist.org/packages/mamitech/datadog-laravel-metric)

Collect Laravel's request histogram data and custom metric for Datadog.

## Feature

- Toggleable via env value
- Laravel middleware integration. By default, it contains these tags
    - app
    - environment
    - action
    - host
    - status_code
- Any default Tags above can be disabled via config

## Limitation

- For now it's only support DogstatsD histogram (microtiming)

## Installation

You can install the package via composer:

```bash
composer require mamitech/datadog-laravel-metric
```

You can publish the config file with:

```bash
php artisan vendor:publish --provider="Mamitech\DatadogLaravelMetric\DatadogLaravelMetricServiceProvider"
```


```

## ENV value

As mentioned in config file, these are the ENV values that can be set for configuration

```
DATADOG_ENABLED
DATADOG_STATSD_SERVER
DATADOG_STATSD_PORT
DATADOG_SOCKET_PATH
DATADOG_HOST
DATADOG_API_KEY
DATADOG_APP_KEY
DATADOG_GLOBAL_TAGS
DATADOG_METRIC_PREFIX
DATADOG_TAGS_APP
DATADOG_TAGS_ENV
DATADOG_MIDDLEWARE_METRIC_NAME
DATADOG_MIDDLEWARE_EXCLUDE_TAGS
```

## Usage

### Laravel Middleware SendRequestDatadogMetric

This Middleware should be auto-added (prepended) to your Laravel app's middleware via Service Provider

### Custom Metric ( `measure` )

```php
use Mamitech\DatadogLaravelMetric\DatadogLaravelMetric;

$func = function () {
    return 'hello this is testing for measure';
};

$startTime = microtime(true);
$result = $func();
$duration = microtime(true) - $startTime;

app(DatadogLaravelMetric::class)->measure('my.metric', ['tag1' => 'value1', 'tag2' => 'value2'], $duration);
```

### Custom Metric Function ( `measureFunc` )

```php
$func = function () {
    return 'hello i am measureFunc';
};

$result = $datadogLaravelMetric->measureFunc('my.metric', ['tag1' => 'value1', 'tag2' => 'value2'], $func);
```

```php
$datadogLaravelMetric = new Mamitech\DatadogLaravelMetric();
echo $datadogLaravelMetric->echoPhrase('Hello, Mamitech!');
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

- [mamitech](https://github.com/mamitech)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
