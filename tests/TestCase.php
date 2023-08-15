<?php

namespace Mamitech\DatadogLaravelMetric\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Mamitech\DatadogLaravelMetric\DatadogLaravelMetricServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Mamitech\\DatadogLaravelMetric\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            DatadogLaravelMetricServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        /*
        $migration = include __DIR__.'/../database/migrations/create_datadog-laravel-metric_table.php.stub';
        $migration->up();
        */
    }
}
