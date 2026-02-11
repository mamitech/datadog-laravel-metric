<?php

use DataDog\DogStatsd;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Route;
use Mamitech\DatadogLaravelMetric\DatadogLaravelMetric;
use Mamitech\DatadogLaravelMetric\Middleware\SendRequestDatadogMetric;

it('sends metric data to datadog when enabled', function () {
    config(['datadog-laravel-metric.enabled' => true]);

    $mockDatadog = Mockery::mock(DogStatsd::class);
    $mockDatadog->shouldReceive('microtiming')->once();
    $datadogLaravelMetric = new DatadogLaravelMetric($mockDatadog);

    $sampleRequestMiddleware = new SendRequestDatadogMetric($datadogLaravelMetric);
    $expectedResponse = new Response;
    $response = $sampleRequestMiddleware->handle(
        new Request,
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
                'domain' => '',
            ]
        )
        ->once();
    $datadogLaravelMetric = new DatadogLaravelMetric($mockDatadog);

    $sampleRequestMiddleware = new SendRequestDatadogMetric($datadogLaravelMetric);
    $expectedResponse = new Response;
    $response = $sampleRequestMiddleware->handle(
        new Request,
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
    $expectedResponse = new Response;
    $response = $sampleRequestMiddleware->handle(
        new Request,
        static function () use ($expectedResponse) {
            return $expectedResponse;
        }
    );

    expect($expectedResponse === $response)->toBeTrue();
});

it('resolves action from controller key correctly', function () {
    config(['datadog-laravel-metric.enabled' => true]);
    config(['datadog-laravel-metric.tags.app' => 'testing-app']);
    config(['datadog-laravel-metric.tags.env' => 'testing']);

    $mockDatadog = Mockery::mock(DogStatsd::class);
    $mockDatadog->shouldReceive('microtiming')
        ->with(
            'request',
            Mockery::any(),
            1,
            Mockery::on(function ($tags) {
                return $tags['action'] === 'App\\Http\\Controllers\\UserController@show';
            })
        )
        ->once();
    $datadogLaravelMetric = new DatadogLaravelMetric($mockDatadog);

    $request = new Request;
    $route = new Route(['GET'], '/users/{id}', ['controller' => 'App\\Http\\Controllers\\UserController@show']);
    $request->setRouteResolver(fn () => $route);

    $sampleRequestMiddleware = new SendRequestDatadogMetric($datadogLaravelMetric);
    $expectedResponse = new Response;
    $response = $sampleRequestMiddleware->handle(
        $request,
        static function () use ($expectedResponse) {
            return $expectedResponse;
        }
    );

    expect($expectedResponse === $response)->toBeTrue();
});

it('resolves action from uses key when controller key is missing', function () {
    config(['datadog-laravel-metric.enabled' => true]);
    config(['datadog-laravel-metric.tags.app' => 'testing-app']);
    config(['datadog-laravel-metric.tags.env' => 'testing']);

    $mockDatadog = Mockery::mock(DogStatsd::class);
    $mockDatadog->shouldReceive('microtiming')
        ->with(
            'request',
            Mockery::any(),
            1,
            Mockery::on(function ($tags) {
                return $tags['action'] === 'App\\Http\\Controllers\\PostController@index';
            })
        )
        ->once();
    $datadogLaravelMetric = new DatadogLaravelMetric($mockDatadog);

    $request = new Request;
    $route = new Route(['GET'], '/posts', ['uses' => 'App\\Http\\Controllers\\PostController@index']);
    $request->setRouteResolver(fn () => $route);

    $sampleRequestMiddleware = new SendRequestDatadogMetric($datadogLaravelMetric);
    $expectedResponse = new Response;
    $response = $sampleRequestMiddleware->handle(
        $request,
        static function () use ($expectedResponse) {
            return $expectedResponse;
        }
    );

    expect($expectedResponse === $response)->toBeTrue();
});

it('resolves action as Closure when route uses a closure', function () {
    config(['datadog-laravel-metric.enabled' => true]);
    config(['datadog-laravel-metric.tags.app' => 'testing-app']);
    config(['datadog-laravel-metric.tags.env' => 'testing']);

    $mockDatadog = Mockery::mock(DogStatsd::class);
    $mockDatadog->shouldReceive('microtiming')
        ->with(
            'request',
            Mockery::any(),
            1,
            Mockery::on(function ($tags) {
                return $tags['action'] === 'Closure';
            })
        )
        ->once();
    $datadogLaravelMetric = new DatadogLaravelMetric($mockDatadog);

    $request = new Request;
    $closure = function () {
        return 'hello';
    };
    $route = new Route(['GET'], '/hello', ['uses' => $closure]);
    $request->setRouteResolver(fn () => $route);

    $sampleRequestMiddleware = new SendRequestDatadogMetric($datadogLaravelMetric);
    $expectedResponse = new Response;
    $response = $sampleRequestMiddleware->handle(
        $request,
        static function () use ($expectedResponse) {
            return $expectedResponse;
        }
    );

    expect($expectedResponse === $response)->toBeTrue();
});

it('resolves action from array syntax [Controller::class, method]', function () {
    config(['datadog-laravel-metric.enabled' => true]);
    config(['datadog-laravel-metric.tags.app' => 'testing-app']);
    config(['datadog-laravel-metric.tags.env' => 'testing']);

    $mockDatadog = Mockery::mock(DogStatsd::class);
    $mockDatadog->shouldReceive('microtiming')
        ->with(
            'request',
            Mockery::any(),
            1,
            Mockery::on(function ($tags) {
                return $tags['action'] === 'App\\Http\\Controllers\\ApiController@store';
            })
        )
        ->once();
    $datadogLaravelMetric = new DatadogLaravelMetric($mockDatadog);

    $request = new Request;
    $route = new Route(['POST'], '/api/items', ['uses' => ['App\\Http\\Controllers\\ApiController', 'store']]);
    $request->setRouteResolver(fn () => $route);

    $sampleRequestMiddleware = new SendRequestDatadogMetric($datadogLaravelMetric);
    $expectedResponse = new Response;
    $response = $sampleRequestMiddleware->handle(
        $request,
        static function () use ($expectedResponse) {
            return $expectedResponse;
        }
    );

    expect($expectedResponse === $response)->toBeTrue();
});

it('resolves action as unknownController@unknownMethod when route has no action', function () {
    config(['datadog-laravel-metric.enabled' => true]);
    config(['datadog-laravel-metric.tags.app' => 'testing-app']);
    config(['datadog-laravel-metric.tags.env' => 'testing']);

    $mockDatadog = Mockery::mock(DogStatsd::class);
    $mockDatadog->shouldReceive('microtiming')
        ->with(
            'request',
            Mockery::any(),
            1,
            Mockery::on(function ($tags) {
                return $tags['action'] === 'unknownController@unknownMethod';
            })
        )
        ->once();
    $datadogLaravelMetric = new DatadogLaravelMetric($mockDatadog);

    $request = new Request;
    $route = new Route(['GET'], '/empty', []);
    $request->setRouteResolver(fn () => $route);

    $sampleRequestMiddleware = new SendRequestDatadogMetric($datadogLaravelMetric);
    $expectedResponse = new Response;
    $response = $sampleRequestMiddleware->handle(
        $request,
        static function () use ($expectedResponse) {
            return $expectedResponse;
        }
    );

    expect($expectedResponse === $response)->toBeTrue();
});

it('resolves action as response@statusCode when route is null', function () {
    config(['datadog-laravel-metric.enabled' => true]);
    config(['datadog-laravel-metric.tags.app' => 'testing-app']);
    config(['datadog-laravel-metric.tags.env' => 'testing']);

    $mockDatadog = Mockery::mock(DogStatsd::class);
    $mockDatadog->shouldReceive('microtiming')
        ->with(
            'request',
            Mockery::any(),
            1,
            Mockery::on(function ($tags) {
                return $tags['action'] === 'response@200';
            })
        )
        ->once();
    $datadogLaravelMetric = new DatadogLaravelMetric($mockDatadog);

    $request = new Request;
    // No route set - route() returns null

    $sampleRequestMiddleware = new SendRequestDatadogMetric($datadogLaravelMetric);
    $expectedResponse = new Response;
    $response = $sampleRequestMiddleware->handle(
        $request,
        static function () use ($expectedResponse) {
            return $expectedResponse;
        }
    );

    expect($expectedResponse === $response)->toBeTrue();
});

it('resolves action as NoRouteMatched@404 when route is null and status is 404', function () {
    config(['datadog-laravel-metric.enabled' => true]);
    config(['datadog-laravel-metric.tags.app' => 'testing-app']);
    config(['datadog-laravel-metric.tags.env' => 'testing']);

    $mockDatadog = Mockery::mock(DogStatsd::class);
    $mockDatadog->shouldReceive('microtiming')
        ->with(
            'request',
            Mockery::any(),
            1,
            Mockery::on(function ($tags) {
                return $tags['action'] === 'NoRouteMatched@404';
            })
        )
        ->once();
    $datadogLaravelMetric = new DatadogLaravelMetric($mockDatadog);

    $request = new Request;
    // No route set - route() returns null

    $sampleRequestMiddleware = new SendRequestDatadogMetric($datadogLaravelMetric);
    $expectedResponse = new Response('', 404);
    $response = $sampleRequestMiddleware->handle(
        $request,
        static function () use ($expectedResponse) {
            return $expectedResponse;
        }
    );

    expect($expectedResponse === $response)->toBeTrue();
});

it('resolves action as MethodNotAllowed@405 when route is null and status is 405', function () {
    config(['datadog-laravel-metric.enabled' => true]);
    config(['datadog-laravel-metric.tags.app' => 'testing-app']);
    config(['datadog-laravel-metric.tags.env' => 'testing']);

    $mockDatadog = Mockery::mock(DogStatsd::class);
    $mockDatadog->shouldReceive('microtiming')
        ->with(
            'request',
            Mockery::any(),
            1,
            Mockery::on(function ($tags) {
                return $tags['action'] === 'MethodNotAllowed@405';
            })
        )
        ->once();
    $datadogLaravelMetric = new DatadogLaravelMetric($mockDatadog);

    $request = new Request;

    $sampleRequestMiddleware = new SendRequestDatadogMetric($datadogLaravelMetric);
    $expectedResponse = new Response('', 405);
    $response = $sampleRequestMiddleware->handle(
        $request,
        static function () use ($expectedResponse) {
            return $expectedResponse;
        }
    );

    expect($expectedResponse === $response)->toBeTrue();
});

it('resolves action as ServerError@500 when route is null and status is 500', function () {
    config(['datadog-laravel-metric.enabled' => true]);
    config(['datadog-laravel-metric.tags.app' => 'testing-app']);
    config(['datadog-laravel-metric.tags.env' => 'testing']);

    $mockDatadog = Mockery::mock(DogStatsd::class);
    $mockDatadog->shouldReceive('microtiming')
        ->with(
            'request',
            Mockery::any(),
            1,
            Mockery::on(function ($tags) {
                return $tags['action'] === 'ServerError@500';
            })
        )
        ->once();
    $datadogLaravelMetric = new DatadogLaravelMetric($mockDatadog);

    $request = new Request;

    $sampleRequestMiddleware = new SendRequestDatadogMetric($datadogLaravelMetric);
    $expectedResponse = new Response('', 500);
    $response = $sampleRequestMiddleware->handle(
        $request,
        static function () use ($expectedResponse) {
            return $expectedResponse;
        }
    );

    expect($expectedResponse === $response)->toBeTrue();
});

it('resolves action as MaintenanceMode@503 when route is null and status is 503', function () {
    config(['datadog-laravel-metric.enabled' => true]);
    config(['datadog-laravel-metric.tags.app' => 'testing-app']);
    config(['datadog-laravel-metric.tags.env' => 'testing']);

    $mockDatadog = Mockery::mock(DogStatsd::class);
    $mockDatadog->shouldReceive('microtiming')
        ->with(
            'request',
            Mockery::any(),
            1,
            Mockery::on(function ($tags) {
                return $tags['action'] === 'MaintenanceMode@503';
            })
        )
        ->once();
    $datadogLaravelMetric = new DatadogLaravelMetric($mockDatadog);

    $request = new Request;

    $sampleRequestMiddleware = new SendRequestDatadogMetric($datadogLaravelMetric);
    $expectedResponse = new Response('', 503);
    $response = $sampleRequestMiddleware->handle(
        $request,
        static function () use ($expectedResponse) {
            return $expectedResponse;
        }
    );

    expect($expectedResponse === $response)->toBeTrue();
});

it('resolves action from invokable object', function () {
    config(['datadog-laravel-metric.enabled' => true]);
    config(['datadog-laravel-metric.tags.app' => 'testing-app']);
    config(['datadog-laravel-metric.tags.env' => 'testing']);

    $mockDatadog = Mockery::mock(DogStatsd::class);
    $mockDatadog->shouldReceive('microtiming')
        ->with(
            'request',
            Mockery::any(),
            1,
            Mockery::on(function ($tags) {
                return $tags['action'] === 'InvokableControllerForTest@__invoke';
            })
        )
        ->once();
    $datadogLaravelMetric = new DatadogLaravelMetric($mockDatadog);

    $request = new Request;
    $invokable = new InvokableControllerForTest;
    $route = new Route(['GET'], '/invokable', ['uses' => $invokable]);
    $request->setRouteResolver(fn () => $route);

    $sampleRequestMiddleware = new SendRequestDatadogMetric($datadogLaravelMetric);
    $expectedResponse = new Response;
    $response = $sampleRequestMiddleware->handle(
        $request,
        static function () use ($expectedResponse) {
            return $expectedResponse;
        }
    );

    expect($expectedResponse === $response)->toBeTrue();
});

it('resolves action from route name when route has only route name', function () {
    config(['datadog-laravel-metric.enabled' => true]);
    config(['datadog-laravel-metric.tags.app' => 'testing-app']);
    config(['datadog-laravel-metric.tags.env' => 'testing']);

    $mockDatadog = Mockery::mock(DogStatsd::class);
    $mockDatadog->shouldReceive('microtiming')
        ->with(
            'request',
            Mockery::any(),
            1,
            Mockery::on(function ($tags) {
                return $tags['action'] === 'name@api.users.index';
            })
        )
        ->once();
    $datadogLaravelMetric = new DatadogLaravelMetric($mockDatadog);

    $request = new Request;
    $route = new Route(['GET'], '/api/users', ['as' => 'api.users.index']);
    $request->setRouteResolver(fn () => $route);

    $sampleRequestMiddleware = new SendRequestDatadogMetric($datadogLaravelMetric);
    $expectedResponse = new Response;
    $response = $sampleRequestMiddleware->handle(
        $request,
        static function () use ($expectedResponse) {
            return $expectedResponse;
        }
    );

    expect($expectedResponse === $response)->toBeTrue();
});

class InvokableControllerForTest
{
    public function __invoke()
    {
        return 'invoked';
    }
}

class TransformerForTest implements \Mamitech\DatadogLaravelMetric\TagTransformer
{
    public function transform(array $data): array
    {
        $data['app'] = 'modified';
        unset($data['environment']);

        return $data;
    }
}

it('transform the tag when transformer exists', function () {
    config([
        'datadog-laravel-metric.enabled' => true,
        'datadog-laravel-metric.tags.app' => 'testing-app',
        'datadog-laravel-metric.tags.environment' => 'testing',
        'datadog-laravel-metric.middleware.tag_transformers' => [TransformerForTest::class],
    ]);

    $mockDatadog = Mockery::mock(DogStatsd::class);
    $mockDatadog->shouldReceive('microtiming')
        ->with(
            'request',
            Mockery::any(),
            1,
            [
                'app' => 'modified',
                'action' => 'unknownController@unknownMethod',
                'domain' => '',
                'status_code' => 200,
            ]
        )
        ->once();
    $datadogLaravelMetric = new DatadogLaravelMetric($mockDatadog);

    $sampleRequestMiddleware = new SendRequestDatadogMetric($datadogLaravelMetric);
    $expectedResponse = new Response;
    $response = $sampleRequestMiddleware->handle(
        new Request,
        static function () use ($expectedResponse) {
            return $expectedResponse;
        }
    );

    expect($expectedResponse === $response)->toBeTrue();
});
