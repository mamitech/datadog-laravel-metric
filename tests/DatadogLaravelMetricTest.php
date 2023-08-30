<?php

use DataDog\DogStatsd;
use Mamitech\DatadogLaravelMetric\DatadogLaravelMetric;

function mockDogstatsd(string $method, bool $receive = true)
{
    $mockDatadog = Mockery::mock(DogStatsd::class);
    if ($receive) {
        $mockDatadog->shouldReceive($method)->once();
    } else {
        $mockDatadog->shouldNotReceive($method);
    }

    return $mockDatadog;
}

dataset('toggle-status', [
    'enabled' => [true, true],
    'disabled' => [false, false],
]);

test('measureFunc', function (bool $enabled, bool $receive) {
    config(['datadog-laravel-metric.enabled' => $enabled]);

    $datadogLaravelMetric = new DatadogLaravelMetric(mockDogstatsd('microtiming', $receive));

    $func = function () {
        return 'hello i am measureFunc';
    };

    $result = $datadogLaravelMetric->measureFunc('my.metric', ['tag1' => 'value1', 'tag2' => 'value2'], $func);

    expect($result === $func())->toBeTrue();
})->with('toggle-status');

test('measure', function (bool $enabled, bool $receive) {
    config(['datadog-laravel-metric.enabled' => $enabled]);

    $datadogLaravelMetric = new DatadogLaravelMetric(mockDogstatsd('microtiming', $receive));

    $func = function () {
        return 'hello this is testing for measure';
    };

    $startTime = microtime(true);
    $result = $func();
    $duration = microtime(true) - $startTime;

    $datadogLaravelMetric->measure('my.metric', ['tag1' => 'value1', 'tag2' => 'value2'], $duration);

    expect($result === $func())->toBeTrue();
})->with('toggle-status');

// testing functions from DogstatsD
test('microtiming', function (bool $enabled, bool $receive) {
    config(['datadog-laravel-metric.enabled' => $enabled]);
    $datadogLaravelMetric = new DatadogLaravelMetric(mockDogstatsd('microtiming', $receive));

    $func = function () {
        return 'hello this is testing for microtiming';
    };

    $startTime = microtime(true);
    $result = $func();
    $duration = microtime(true) - $startTime;

    $datadogLaravelMetric->microtiming(
        stat: 'my.metric',
        tags: ['tag1' => 'value1', 'tag2' => 'value2'],
        time: $duration
    );

    expect($result === $func())->toBeTrue();
})->with('toggle-status');

foreach ([
    'increment',
    'decrement',
] as $method) {
    test($method, function (bool $enabled, bool $receive) use ($method) {
        config(['datadog-laravel-metric.enabled' => $enabled]);
        $datadogLaravelMetric = new DatadogLaravelMetric(mockDogstatsd($method, $receive));

        $func = function () use ($method) {
            return 'hello this is testing for '.$method;
        };

        $startTime = microtime(true);
        $result = $func();
        $duration = microtime(true) - $startTime;

        $datadogLaravelMetric->$method(
            stats: 'my.metric',
            tags: ['tag1' => 'value1', 'tag2' => 'value2'],
            value: 1
        );

        expect($result === $func())->toBeTrue();
    })->with('toggle-status');
}

foreach ([
    'gauge',
    'set',
    'histogram',
    'distribution',
] as $method) {
    test($method, function (bool $enabled, bool $receive) use ($method) {
        config(['datadog-laravel-metric.enabled' => $enabled]);

        $datadogLaravelMetric = new DatadogLaravelMetric(mockDogstatsd($method, $receive));

        $func = function () use ($method) {
            return 'hello this is testing for '.$method;
        };

        $startTime = microtime(true);
        $result = $func();
        $duration = microtime(true) - $startTime;

        $datadogLaravelMetric->$method(
            stat: 'my.metric',
            tags: ['tag1' => 'value1', 'tag2' => 'value2'],
            value: $duration
        );

        expect($result === $func())->toBeTrue();
    })->with('toggle-status');
}
