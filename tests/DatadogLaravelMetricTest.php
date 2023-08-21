<?php

use DataDog\DogStatsd;
use Mamitech\DatadogLaravelMetric\DatadogLaravelMetric;

test('measureFunc', function () {
    config(['datadog-laravel-metric.enabled' => true]);

    $mockDatadog = Mockery::mock(DogStatsd::class);
    $mockDatadog->shouldReceive('microtiming')->once();
    $datadogLaravelMetric = new DatadogLaravelMetric($mockDatadog);

    $func = function () {
        return 'hello i am measureFunc';
    };

    $result = $datadogLaravelMetric->measureFunc('my.metric', ['tag1' => 'value1', 'tag2' => 'value2'], $func);

    expect($result === $func())->toBeTrue();
});

test('measure', function () {
    config(['datadog-laravel-metric.enabled' => true]);

    $mockDatadog = Mockery::mock(DogStatsd::class);
    $mockDatadog->shouldReceive('microtiming')->once();
    $datadogLaravelMetric = new DatadogLaravelMetric($mockDatadog);

    $func = function () {
        return 'hello this is testing for measure';
    };

    $startTime = microtime(true);
    $result = $func();
    $duration = microtime(true) - $startTime;

    $datadogLaravelMetric->measure('my.metric', ['tag1' => 'value1', 'tag2' => 'value2'], $duration);

    expect($result === $func())->toBeTrue();
});
