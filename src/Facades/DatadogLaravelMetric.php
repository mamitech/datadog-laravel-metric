<?php

namespace Mamitech\DatadogLaravelMetric\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Mamitech\DatadogLaravelMetric\DatadogLaravelMetric
 */
class DatadogLaravelMetric extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Mamitech\DatadogLaravelMetric\DatadogLaravelMetric::class;
    }
}
