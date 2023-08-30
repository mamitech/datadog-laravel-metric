<?php

use DataDog\DogStatsd;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Mamitech\DatadogLaravelMetric\DatadogLaravelMetric;
use Mamitech\DatadogLaravelMetric\Middleware\SendRequestDatadogMetric;

it('sends metric data to datadog when enabled', function () {
    config(['datadog-laravel-metric.enabled' => true]);

    $mockDatadog = Mockery::mock(DogStatsd::class);
    $mockDatadog->shouldReceive('microtiming')->once();
    $datadogLaravelMetric = new DatadogLaravelMetric($mockDatadog);

    $sampleRequestMiddleware = new SendRequestDatadogMetric($datadogLaravelMetric);
    $expectedResponse = new Response();
    $response = $sampleRequestMiddleware->handle(
        new Request(),
        static function () use ($expectedResponse) {
            return $expectedResponse;
        }
    );

    expect($expectedResponse === $response)->toBeTrue();
});

it('sends metric data to datadog and exclude tag as configured', function () {
    config(['datadog-laravel-metric.enabled' => true]);
    config(['datadog-laravel-metric.tags.app' => 'testing-app']);
    config(['datadog-laravel-metric.tags.env' => 'testing']);
    config(['datadog-laravel-metric.middleware.exclude_tags' => ['status_code']]);

    $mockDatadog = Mockery::mock(DogStatsd::class);
    $mockDatadog->shouldReceive('microtiming')
        ->with(
            'request',
            Mockery::any(),
            1,
            [
                'app' => 'testing-app',
                'environment' => 'testing',
                'action' => 'unknownController@unknownMethod',
                'host' => '',
            ]
        )
        ->once();
    $datadogLaravelMetric = new DatadogLaravelMetric($mockDatadog);

    $sampleRequestMiddleware = new SendRequestDatadogMetric($datadogLaravelMetric);
    $expectedResponse = new Response();
    $response = $sampleRequestMiddleware->handle(
        new Request(),
        static function () use ($expectedResponse) {
            return $expectedResponse;
        }
    );

    expect($expectedResponse === $response)->toBeTrue();
});

it('does not send metric data to datadog when disabled', function () {
    config(['datadog-laravel-metric.enabled' => false]);

    $mockDatadog = Mockery::mock(DogStatsd::class);
    $mockDatadog->shouldNotReceive('microtiming');
    $datadogLaravelMetric = new DatadogLaravelMetric($mockDatadog);

    $sampleRequestMiddleware = new SendRequestDatadogMetric($datadogLaravelMetric);
    $expectedResponse = new Response();
    $response = $sampleRequestMiddleware->handle(
        new Request(),
        static function () use ($expectedResponse) {
            return $expectedResponse;
        }
    );

    expect($expectedResponse === $response)->toBeTrue();
});
