# datadog-laravel-metric

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mamitech/datadog-laravel-metric.svg?style=flat-square)](https://packagist.org/packages/mamitech/datadog-laravel-metric)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/mamitech/datadog-laravel-metric/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/mamitech/datadog-laravel-metric/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/mamitech/datadog-laravel-metric/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/mamitech/datadog-laravel-metric/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/mamitech/datadog-laravel-metric.svg?style=flat-square)](https://packagist.org/packages/mamitech/datadog-laravel-metric)

Collect Laravel's request histogram data and custom metric for Datadog.

## Requirement

- PHP 8 or later
- Laravel 9 or later

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

### (optional) add alias to your app

You can add alias to simplify call the DatadogLaravelMetric API

Add this into your Laravel `config/app.php` inside the array of `'aliases'`
```php
'aliases' => [
    // ommitted
    'DatadogMetric' => Mamitech\DatadogLaravelMetric\Facades\DatadogLaravelMetric::class,
];
```

## Configs

These are the configuration (as included in `config/datadog-laravel-metric.php`)

### 'enabled' 

Toggle the feature on/off

### 'init_config' 

These are configs to initialize DogstatsD object, the main class for sending metric to DataDog

#### 'host' 

Datadog Agent (or specifically DogstatsD) host address

#### 'port' 

Datadog Agent (or specifically DogstatsD) port. The default is 8125.

#### 'socket_path' 

from DogstatsD docs: The path to the DogStatsD Unix domain socket (overrides host and port). This is only supported with Agent v6+. https://docs.datadoghq.com/developers/dogstatsd/?code-lang=php&tab=hostagent#client-instantiation-parameters

#### 'datadog_host' 

The host of the DataDog you send metric data to. The default is 'https://app.datadoghq.com' .

#### 'api_key' 

API key you get on your DataDog account.

#### 'app_key' 

APP key you generate on your DataDog account.

#### 'global_tags' 

Tags that you want to include everywhere every time sending metric from your app. Formatted as array with key-value.

#### 'metric_prefix' 

Global prefix to each metric name you set.

### 'tags' 

List of important tags. Mainly used for Middleware.

#### 'app' 

The name of the app.

#### 'env' 

The environment the app runs.

### 'middleware' 

Specific config regarding Middleware.

### 'metric_name' 

Specify the metric name for each request data. The default is `request`.

### 'exclude_tags' 

On the Middleware, you can exclude certain tags from being sent to datadog. Put them in a comma separated string.

List of possible tags (by default those tags are sent as metric data):

- app
- environment
- action
- host
- status_code

## ENV value

As mentioned in config file, these are the ENV values that can be set for configuration

```
DATADOG_METRIC_ENABLED
DATADOG_STATSD_SERVER
DATADOG_STATSD_PORT
DATADOG_SOCKET_PATH
DATADOG_HOST
DATADOG_API_KEY
DATADOG_APP_KEY
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
use Mamitech\DatadogLaravelMetric\DatadogLaravelMetric;

$func = function () {
    return 'hello i am measureFunc';
};

$result = app(DatadogLaravelMetric::class)->measureFunc('my.metric', ['tag1' => 'value1', 'tag2' => 'value2'], $func);
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
