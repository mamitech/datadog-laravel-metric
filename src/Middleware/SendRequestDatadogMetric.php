<?php

namespace Mamitech\DatadogLaravelMetric\Middleware;

use Closure;
use Illuminate\Http\Request;
use Mamitech\DatadogLaravelMetric\DatadogLaravelMetric;
use Mamitech\DatadogLaravelMetric\TagTransformer;

class SendRequestDatadogMetric
{
    private const DEFAULT_ACTION = 'unknownController@unknownMethod';

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
        $statusCode = $response?->getStatusCode() ?? 500;
        $action = $this->resolveAction($request, $statusCode);
        $tags = [
            'app' => config('datadog-laravel-metric.tags.app'),
            'environment' => config('datadog-laravel-metric.tags.env'),
            'action' => $action,
            'domain' => $request->getHost(),
            'status_code' => $statusCode,
        ];

        // exclude certain tags from being sent to datadog
        $excludeTags = config('datadog-laravel-metric.middleware.exclude_tags');
        foreach ($excludeTags as $excludeTag) {
            unset($tags[$excludeTag]);
        }

        $tagTransformers = config('datadog-laravel-metric.middleware.tag_transformers');
        // check if $tagTransformers is an array
        if (is_array($tagTransformers)) {
            foreach ($tagTransformers as $transClass) {
                $transformer = app($transClass);
                if (! $transformer instanceof TagTransformer) {
                    throw new \Exception("Class $transClass must implement Mamitech\DatadogLaravelMetric\TagTransformer");
                }
                $tags = $transformer->transform($tags);
            }
        }

        // send to Datadog
        $metricName = config('datadog-laravel-metric.middleware.metric_name');
        $this->datadogLaravelMetric->measure($metricName, $tags, $duration);

        return $response;
    }

    /**
     * Resolve the action name from the request route.
     */
    private function resolveAction(Request $request, int $statusCode): string
    {
        $route = $request->route();

        if ($route === null) {
            return match ($statusCode) {
                404 => 'NoRouteMatched@404',
                405 => 'MethodNotAllowed@405',
                500 => 'ServerError@500',
                503 => 'MaintenanceMode@503',
                default => 'response@'.$statusCode,
            };
        }

        $routeAction = $route->getAction();

        // Case 1: Controller string in 'controller' key (e.g., 'App\Http\Controllers\UserController@show')
        if (isset($routeAction['controller']) && is_string($routeAction['controller'])) {
            return $routeAction['controller'];
        }

        // Case 2: Check 'uses' key for controller string or Closure
        if (isset($routeAction['uses'])) {
            $uses = $routeAction['uses'];

            // String controller reference
            if (is_string($uses)) {
                return $uses;
            }

            // Closure route
            if ($uses instanceof \Closure) {
                return 'Closure';
            }

            // Array syntax: [Controller::class, 'method']
            if (is_array($uses) && count($uses) === 2) {
                return $uses[0].'@'.$uses[1];
            }

            // Invokable object (callable with __invoke method)
            if (is_object($uses) && method_exists($uses, '__invoke')) {
                return get_class($uses).'@__invoke';
            }
        }

        // Case 3: Route name as fallback (e.g., Route::view(), Route::redirect())
        if (isset($routeAction['as']) && is_string($routeAction['as'])) {
            return 'name@'.$routeAction['as'];
        }

        return self::DEFAULT_ACTION;
    }
}
